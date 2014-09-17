#!/bin/bash

############################################################################################
#
# Automatic Distribution Build and Tag creating on GitHub
#
############################################################################################
#
# Script by default does the following steps:
# - creates temp directory
# - clones git repository there
# - creates temp branch
# - installs composer and nodes dependencies
# - runs grunt install ( if available )
# - adds vendor directory ( composer dependencies ) to commit
# - clears out build
# - commits build to temp branch
# - creates new tag
# - removes temp branch
# - removes temp directory
#
############################################################################################
#
# Options:
# - $1 ( $RELEASE_VERSION ) - Optional. Tag version which will be created for current build
# - $2 ( $BUILD_TYPE ) - Optional. Default 'production'. If build type is 'production'
# script creates temp directory and does production build there from scratch. Use it to have 
# Production distributive. On any other value script prepares current build, but ignores 
# dependencies installation ( it does not call composer install, npm install ).
#
############################################################################################
#
# Features:
# - The current script generates new Tag on GitHub for your build (Distributive).
# - It can use the latest commit log for creating new tag. Log message should 
# contain [release:{tag}] shortcode
# - circleci compatible.
#
############################################################################################
#
# Examples:
#
# Run remote sh file:
# curl -s https://url-to-build-sh-file.sh | RELEASE_VERSION=1.2.3 BUILD_TYPE=production  sh
#
# Run local sh file
# sh build.sh 1.2.3 production
#
# Run grunt task ( see information about gruntfile.js below )
# grunt build:1.2.3
#
############################################################################################
#
# CircleCi
# The current script can be triggered on CircleCi.
# Add the following settings to your circle.yml file:
# 
# deployment:
#   production:
#     branch: master
#       commands:
#         - sh <(curl -s https://url-do-a-gist-file-with-sh-commands.sh)
#
# Notes: 
# - script will be triggered only on successful (green) build for 'master' branch in 
# current example.
# - in random cases gist file is not available on curl request, I suggest to 
# download script and call it directly.
#
# More details about CircleCi deployment:
# https://circleci.com/docs/configuration#deployment
#
############################################################################################
#
# Gruntfile.js
#
# module.exports = function build( grunt ) {
#
#  grunt.initConfig( {
#
#    shell: {
#      build: {
#        command: function( tag, build_type ) {
#          return 'sh build.sh ' + tag + ' ' + build_type;
#        },
#        options: {
#          encoding: 'utf8',
#          stderr: true,
#          stdout: true
#        }
#      }
#     }
#     
#   } );
#
#   grunt.registerTask( 'build', 'Run Build tasks.', function( tag, build_type ) {
#     if ( tag == null ) grunt.warn( 'Build tag must be specified, like build:1.0.0' );
#     if( build_type == null ) build_type = 'production';
#     grunt.task.run( 'shell:build:' + tag + ':' + build_type );
#   });
#
# }
#
#
######################################################################################

echo " "
echo "Running build script..."
echo "---"

if [ -z $RELEASE_VERSION ] ; then
 
  # Try to get Tag version which should be created.
  if [ -z $1 ] ; then
    echo "Tag version parameter is not passed."
    echo "Determine if we have [release:{version}] shortcode to deploy new release"
    RELEASE_VERSION="$( git log -1 --pretty=%s | sed -n 's/.*\[release\:\(.*\)\].*/\1/p' )"  
  else
    echo "Tag version parameter is "$1
    RELEASE_VERSION=$1
  fi
  
else 
 
  echo "Tag version parameter is "$RELEASE_VERSION
 
fi

# Set BUILD_TYPE env
# It's being used to determine if we should create production build.
if [ -z $BUILD_TYPE ] ; then
  if [ -z $2 ] ; then
    BUILD_TYPE=production
  else
    BUILD_TYPE=$2
  fi
fi
echo "Build type is "$BUILD_TYPE
echo "---"

if [ -z $RELEASE_VERSION ] ; then

  echo "No [release:{tag}] shortcode found."
  echo "Finish process."
  exit 0
  
else

  echo "Determine current branch:"
  if [ -z $CIRCLE_BRANCH ]; then
    CIRCLE_BRANCH=$(git rev-parse --abbrev-ref HEAD)
  fi
  echo $CIRCLE_BRANCH
  echo "---"

  # Determine if we need to do production release.
  if [ $BUILD_TYPE = "production" ]; then
    
    # Remove temp directory if it already exists to prevent issues before proceed
    if [ -d temp-build-$RELEASE_VERSION ]; then
      rm -rf temp-build-$RELEASE_VERSION
    fi
    
    echo "Create temp directory"
    mkdir temp-build-$RELEASE_VERSION
    cd temp-build-$RELEASE_VERSION
    
    echo "Do production build from scratch to temp directory"
    ORIGIN_URL="$( git config --get remote.origin.url )"
    git clone $ORIGIN_URL
    cd "$( basename `git rev-parse --show-toplevel` )"
    # Be sure we are on the same branch
    git checkout $CIRCLE_BRANCH
    echo "---"
    
    echo "Install dependencies:"
    echo "Running: npm install --production"
    npm config set loglevel silent
    npm install --production
    echo "Running: composer install --no-dev --no-interaction"
    composer install --no-dev --no-interaction --quiet
  	echo "---"
  	
  fi
  
  echo "Create local and remote temp branch temp-automatic-branch-"$RELEASE_VERSION
  git checkout -b temp-branch-$RELEASE_VERSION
  git push origin temp-branch-$RELEASE_VERSION
  git branch --set-upstream-to=origin/temp-branch-$RELEASE_VERSION temp-branch-$RELEASE_VERSION
  echo "---"

  echo "Set configuration to proceed"
  git config --global push.default simple
  git config --global user.email "$( git log -1 --pretty=%an )"
  git config --global user.name "$( git log -1 --pretty=%ae )"
  echo "---"

  echo "Add/remove files"
  git add --all
  echo "Exclude circleci specific files and logs if they exist"
  git rm --cached coverage.clover
  git rm --cached ocular.phar
  git rm -r --cached build
  echo "Be sure we do not add node files"
  git rm -r --cached node_modules
  echo "Be sure we do not add .git directories"
  find ./vendor -name .git -exec rm -rf '{}' \;
  echo "Be sure we do not add .svn directories"
  find ./vendor -name .svn -exec rm -rf '{}' \;
  echo "Be sure we added vendor directory"
  git add -f vendor
  echo "---"
  
  echo "Now commit our build to remote branch"
  git commit -m "[ci skip] Distributive Auto Build" --quiet
  git pull
  git push --quiet
  echo "---"

  echo "Finally, create tag "$RELEASE_VERSION
  git tag -a $RELEASE_VERSION -m "v"$RELEASE_VERSION" - Distributive Auto Build"
  git push origin $RELEASE_VERSION
  echo "---"

  echo "Remove local and remote temp branches, but switch to previous branch before"
  git checkout $CIRCLE_BRANCH
  git push origin --delete temp-branch-$RELEASE_VERSION
  git branch -D temp-branch-$RELEASE_VERSION
  echo "---"
  
  # Remove temp directory.
  if [ $BUILD_TYPE = "production" ]; then
    echo "Remove temp directory"
    cd ../..
    rm -rf temp-build-$RELEASE_VERSION
    echo "---"
  fi
  
  echo "Done"

fi 
