#!/usr/bin/env python
'''
Push feed and vcs activities to an IRC channel.  Configured with the ".slander" rc file, or another yaml file specified on the cmd line.

CREDITS
Miki Tebeka, http://pythonwise.blogspot.com/2009/05/subversion-irc-bot.html
Eloff, http://stackoverflow.com/a/925630
rewritten by Adam Wight,
project homepage is https://github.com/adamwight/slander

EXAMPLE
This is the configuration file used for the CiviCRM project:
    jobs:
        svn:
            changeset_url_format:
                https://fisheye2.atlassian.com/changelog/CiviCRM?cs=%s
            root: http://svn.civicrm.org/civicrm
            args: --username SVN_USER --password SVN_PASSS
        jira:
            base_url:
                http://issues.civicrm.org/jira
            source:
                http://issues.civicrm.org/jira/activity?maxResults=20&streams=key+IS+CRM&title=undefined

    irc:
        host: irc.freenode.net
        port: 6667
        nick: civi-activity
        realname: CiviCRM svn and jira notification bot
        channel: "#civicrm" #note that quotes are necessary here
        maxlen: 200

    poll_interval: 60

    sourceURL: https://svn.civicrm.org/tools/trunk/bin/scripts/ircbot-civi.py
'''

import sys
import os
import re

import yaml

from twisted.words.protocols import irc
from twisted.internet.protocol import ReconnectingClientFactory
from twisted.internet import reactor
from twisted.internet.task import LoopingCall

from xml.etree.cElementTree import parse as xmlparse
from cStringIO import StringIO
from subprocess import Popen, PIPE

import feedparser
from HTMLParser import HTMLParser


class RelayToIRC(irc.IRCClient):
    def connectionMade(self):
        self.config = self.factory.config
        self.jobs = create_jobs(self.config["jobs"])
        self.nickname = self.config["irc"]["nick"]
        self.realname = self.config["irc"]["realname"]
        self.channel = self.config["irc"]["channel"]
        self.sourceURL = "https://github.com/adamwight/slander"
        if "sourceURL" in self.config:
            self.sourceURL = self.config["sourceURL"]

        irc.IRCClient.connectionMade(self)

    def signedOn(self):
        self.join(self.channel)

    def joined(self, channel):
        print "Joined channel %s as %s" % (channel, self.nickname)
        task = LoopingCall(self.check)
        task.start(self.config["poll_interval"])
        print "Started polling jobs, every %d seconds." % (self.config["poll_interval"], )

    def privmsg(self, user, channel, message):
        if message.find(self.nickname) >= 0:
            # TODO surely there are useful ways to interact?
            if re.search(r'\bhelp\b', message):
                self.say(self.channel, "If I only had a brain: %s" % (self.sourceURL, ))
            else:
                print "Failed to handle incoming command: %s said %s" % (user, message)

    def check(self):
        for job in self.jobs:
            for line in job.check():
                self.say(self.channel, str(line))
                print(line)

    @staticmethod
    def run(config):
        factory = ReconnectingClientFactory()
        factory.protocol = RelayToIRC
        factory.config = config
        reactor.connectTCP(config["irc"]["host"], config["irc"]["port"], factory)
        reactor.run()

class SvnPoller(object):
    def __init__(self, root=None, args=None, changeset_url_format=None):
        self.pre = ["svn", "--xml"] + args.split()
        self.root = root
        self.changeset_url_format = changeset_url_format
        print "Initializing SVN poller: %s" % (" ".join(self.pre)+" "+root, )

    def svn(self, *cmd):
        pipe = Popen(self.pre +  list(cmd) + [self.root], stdout=PIPE)
        try:
            data = pipe.communicate()[0]
        except IOError:
            data = ""
        return xmlparse(StringIO(data))

    def revision(self):
        tree = self.svn("info")
        revision = tree.find(".//commit").get("revision")
        return int(revision)

    def revision_info(self, revision):
        revision = str(revision)
        tree = self.svn("log", "-r", revision)
        author = tree.find(".//author").text
        comment = truncate(strip(tree.find(".//msg").text), self.config["irc"]["maxlen"])
        url = self.changeset_url(revision)

        return (revision, author, comment, url)

    def changeset_url(self, revision):
        return self.changeset_url_format % (revision, )

    previous_revision = None
    def check(self):
        try:
            latest = self.revision()
            if self.previous_revision and latest != self.previous_revision:
                for rev in range(self.previous_revision + 1, latest + 1):
                    yield "r%s by %s: %s [%s]" % self.revision_info(rev)
            self.previous_revision = latest
        except Exception, e:
            print "ERROR: %s" % e


class FeedPoller(object):
    last_seen_id = None

    def __init__(self, **config):
        print "Initializing feed poller: %s" % (config["source"], )
        self.config = config

    def check(self):
        result = feedparser.parse(self.config["source"])
        for entry in result.entries:
            if (not self.last_seen_id) or (self.last_seen_id == entry.id):
                break
            yield self.parse(entry)

        if result.entries:
            self.last_seen_id = result.entries[0].id


class JiraPoller(FeedPoller):
    def parse(self, entry):
        m = re.search(r'(CRM-[0-9]+)$', entry.link)
        if (not m) or (entry.generator_detail.href != self.config["base_url"]):
            return
        issue = m.group(1)
        summary = truncate(strip(entry.summary), self.config["irc"]["maxlen"])
        url = self.config["base_url"]+"/browse/%s" % (issue, )

        return "%s: %s %s [%s]" % (entry.author_detail.name, issue, summary, url)

class MinglePoller(FeedPoller):
    def parse(self, entry):
        m = re.search(r'^(.*/([0-9]+))', entry.id)
        url = m.group(1)
        issue = int(m.group(2))
        summary = truncate(strip(entry.summary), self.config["irc"]["maxlen"])
        author = abbrevs(entry.author_detail.name)

        return "#%d: (%s) %s [%s]" % (issue, author, summary, url)

def strip(text, html=True, space=True):
    class MLStripper(HTMLParser):
        def __init__(self):
            self.reset()
            self.fed = []
        def handle_data(self, d):
            self.fed.append(d)
        def get_data(self):
            return ''.join(self.fed)

    if html:
        stripper = MLStripper()
        stripper.feed(text)
        text = stripper.get_data()
    if space:
        text = text.strip().replace("\n", " ")
    return text

def abbrevs(name):
    return "".join([w[:1] for w in name.split()])

def truncate(message, length):
    if len(message) > length:
        return (message[:(length-3)] + "...")
    else:
        return message


def create_jobs(d):
    for type, options in d.items():
        classname = type.capitalize() + "Poller"
        klass = globals()[classname]
        yield klass(**options)

if __name__ == "__main__":
    if len(sys.argv) == 2:
        dotfile = sys.argv[1]
    else:
        dotfile = os.path.expanduser("~/.slander")
    print "Reading config from %s" % (dotfile, )
    config = yaml.load(file(dotfile))
    RelayToIRC.run(config)
