PHPChangedBinaries
==================

__EARLY ALPHA:__ This software is very much in the early alpha status. If nothing else, it needs a code tidy! It works well, but hasn't had anything approaching thorough testing yet.


Introduction
-------------

PHPChangedBinaries is a simple PHP script to compare current a calculated checksum of various system files against a previously stored value. It's designed to achieve two jobs;

1. detect when system files change unexpectedly
2. notify the sysadmin of the change

Whilst the script is effective, PHP is not necessarily the best language to use for this task. This project exists largely to help map out functionality in preparation for implementation of something similar in a compiled language.

The greatest security benefits come when used in conjunction with [RemoteHashStore](http://www.bentasker.co.uk/documentation/security/197-remotehashstore-documentation "RemoteHashStore Client Documentation") as it moves the stored hashes off the server being checked, as well as making it harder for an attacker to tamper with ChangedBinaries and go un-noticed.


License
--------

PHPChangedBinaries is licensed under the GNU GPL V2. See the LICENSE file for a copy of the License.


Installation
-------------

Download archives will be provided once the software is stable, in the meantime, simply grab a copy of this repo.

For details on configuration, see the [PHPChangedBinaries Documentation](http://www.bentasker.co.uk/documentation/security/196-php-changed-binaries "PHPChanged Binaries Documentation").