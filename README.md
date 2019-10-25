# Wopi

This is an application for https://nextcloud.com/ that allows you 
to edit office documents in Microsoft office online server. 
The application implements the api described on the page https://wopi.readthedocs.io/projects/wopirest/en/latest/. 
List of implemented methods
* FILE OPERATIONS
  * CheckFileInfo
  * GetFile
  * Lock
  * GetLock
  * RefreshLock
  * Unlock
  * UnlockAndRelock
  * PutFile
  * PutUserInfo


## Building the app

The app can be built by using the provided Makefile by running:

    make

This requires the following things to be present:
* make
* which
* tar: for building the archive
* curl: used if phpunit and composer are not installed to fetch them from the web

The make command will install or update Composer dependencies.
The archive is then located in build/artifacts/appstore.

## Installation

Build app then place unzipped archive in **nextcloud/apps/**.

## Settings

Application settings are in the additional section.
* Office server url - root url of office online server

## Network configuration

The office server must have access to the nextcloud server at the same 
address at which the user accesses the nextcloud server.
