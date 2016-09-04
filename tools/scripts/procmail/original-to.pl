#!/usr/bin/perl -w
# for use as a procmail filter
#
# :0
# * X-Original-To: bounce.*
# | original-to.pl

use strict;
use MIME::Parser;
use MIME::Entity;

my $parser = new MIME::Parser;

my $entity = $parser->parse(\*STDIN);

my $orig = $entity->head->get('X-Original-To',0);
my $to = $entity->head->get('To',0);

$entity->head->replace('To', $orig);
$entity->head->replace('X-Original-To', $to);

$entity->smtpsend(Host => 'localhost');
