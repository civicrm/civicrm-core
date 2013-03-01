#!/usr/bin/env bash -v

# Where are we called from?
P=`dirname $0`

# Current dir
ORIGPWD=`pwd`

# Function to Create Folder where documentation will be generated. 
create_doc_folder()
{
    cd $ORIGPWD/../
    
    if [ ! -d "Documentation" ] ; then 
	mkdir Documentation
    fi
}

create_documentation()
{
    #
    # folder to be parsed
    #
    PARSE_FOLDER=$ORIGPWD/../
    
    #
    # target folder (documents will be generated in this folder)
    #
    TARGET_FOLDER=$ORIGPWD/../Documentation
    
    #
    # title of generated documentation
    # 
    TITLE="CiviCRM"
    
    #
    # parse @internal and elements marked private with @access
    #
    PRIVATE=on
    
    #
    # JavaDoc-compliant description parsing
    #
    JAVADOC_STYLE=off
    
    #
    # parse a PEAR-style repository
    #
    PEAR_STYLE=on
    
    #
    # generate highlighted sourcecode for every parced file
    #
    SOURCECODE=on
    
    #
    # output information (output:converter:templatedir)
    #
    OUTPUT=HTML:frames:phpedit
    
    phpdoc -t $TARGET_FOLDER -o $OUTPUT -d $PARSE_FOLDER -ti "$TITLE" -pp $PRIVATE -j $JAVADOC_STYLE -p $PEAR_STYLE -s $SOURCECODE
}

# Main Execution Starts Here.

create_doc_folder

create_documentation
