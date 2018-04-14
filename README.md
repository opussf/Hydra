# Hydra
RSS feed system supporting multiple heads.

## History
A previous version of this system was designed to schedule, and slowly feed episodes of Buffy The Vampire Slayer, and Angel.
The scheduling was done to put the episodes into the correct order to capture how they should be viewed (with the cross over content).

This worked, but made it tricky to schedule other content into it.
Thus a rewrite took place to make it support many sources of content that could be controlled.

## Setup
Create a place you want to serve content from.
(A path on a web server.)
Copy the contents of ```webCode``` to that location.
Rename ```htaccess``` to ```.htaccess```.

Secure this if you want.
I use http auth through Apache2 configuration.

Decide how often you want to run the ```postShow.py``` script.
I run it once an hour at 10 minutes past the hour.
Set this up in crontab (or some such thing like that).

```10 * * * * <PATH_TO_HYDRA>/postShow.py -d >> <YOUR_LOG_FILENAME> 2>&1```

Running ```postShow.py``` without the -d runs in dryrun mode.
Log contents will be created, no changes will be made.

By default, this will auto remove posted files after 13 weeks.
Either edit the script, or pass the -a, --age parameter to change the expiration period.

## Content
Content is divided into heads.
The structure of a head is:

```
.
`-- headfolder
	|-- src
	`-- cron.txt
```

Copy your source content into src.
Order it with the modification date.
Older files will be posted first.

```cron.txt``` controls when this head will post content.
Put any number of cron patterns in cron.txt.
If any of them match, the oldest file in src will be copied to headfolder.

Note: if you copy files into src/ one at a time, the modification date will reflect your actions.

### cron.txt
If you are running this once an hour, I recommend leaving the cron pattern minute field as *.

IE ```* 10 * * *```  -- What ever minute the job runs, matches 10 am.

With lots of content, the ```postShow.py``` may take a while to copy all the files, and matches may try to happen up to a few miuntes later.
If it is scheduled to run more than once an hour, you will want to give a range of values for the minute field.

IE ```15-29 10 * * *``` -- If you run it on the quarter hour, this will give it a 14 minute window, and only publish at about the 2nd quarter.

### Supported file types.
This currently only supports .m4v, .mp4, and .mp3 file extensions.
Modify ```rss.inc.php``` to add file types you want to support.
Remember to make sure that RSS will support the new formats.

Note that this does not limit the filetypes posted, it only provides a way of mapping the mime types to file extensions.

### Support files.
For each content file (.m4v, .mp4, or .mp3), there can be an .ifo file.
(Replace the .m4v, .mp4 or .mp3 with .ifo.)
.ifo files will get moved, and should be removed along with their corrisponding content file.

The .ifo file is a 2 line text file.
The first line will be used for the title of the RSS item (defaults to the path/filename).
The second and more lines will be published in the RSS item description (defaults to a short string).

## Extras
The posting process, even just a dryrun, will create a file ```future.json```.
This drives ```future.php``` to show what is coming up.

## Known bugs and issues
* .ifo files created in the posting area may not get properly removed with their parent content.
	- They won't do anything, but they will exist.
	- It is okay to just delete them.
* There is no option to auto clean content folders after all the posted content is expired.
	- A content folder with just a text file and a directory should not be taking up too much space
	- This does actually preserve the cron.txt until you want to remove it.
* There is no option to not generate the ```future.json``` file.
* Since this does a copy of the file, and the RSS is created on demand, if the RSS file is served during the copy, it might report an invalid content size.  This would cause iTunes to think the file is invalid.
	- A few possible fixes. One would be to copy the files as temp names to the publish path, and then rename it to the content name.
	- For a higher load system, one might look into generating all possible RSS feeds once content is published.  This is a bigger rewrite.

## Notes
I never really meant to make this public, so there are some things that are hard coded that should have configuration options.
The photo in the feed is one of those items.

There are also some things I'm sort of working on here and there.
This is mostly fun for now.

# Media Server
192.168.1.67

Implement the RTSP v2.0 https://tools.ietf.org/html/rfc7826

RTSP and RTSPS

RTSP = port 554
RTSPS= port 322
Alternate port: 8554
port: 1755?


UPnP / DLNA server  (Digital Living Network Alliance)

UDP port: 1900

https://github.com/ps3mediaserver/ps3mediaserver/blob/master/src/main/external-resources/documentation/networking.html
https://spirespark.com/dlna/guidelines
https://www.rfc3092.net/category/dlna/
https://www.google.com/search?q=media+server+protocol&ie=utf-8&oe=utf-8

https://wiki.python.org/moin/UdpCommunication

http://www.svlconnectsdk.com/download

https://openconnectivity.org/developer/specifications/upnp-resources/upnp
https://en.wikipedia.org/wiki/Universal_Plug_and_Play#UPnP_AV_standards

Multicast:
Seems that this needs to be found via multicast discovery.
How to enable that?

https://en.wikipedia.org/wiki/Multicast_Source_Discovery_Protocol
https://tools.ietf.org/html/rfc3618
https://tools.ietf.org/html/rfc4611


https://tools.ietf.org/html/rfc2616


https://tools.ietf.org/html/rfc4601
 Hello messages MUST be sent on all active interfaces, including
   physical point-to-point links, and are multicast to the 'ALL-PIM-
   ROUTERS' group address ('224.0.0.13' for IPv4 and 'ff02::d' for
   IPv6).




https://en.wikipedia.org/wiki/Multiprotocol_BGP
https://tools.ietf.org/html/rfc4760

https://stackoverflow.com/questions/21089268/python-service-discovery-advertise-a-service-across-a-local-network
https://pymotw.com/2/socket/multicast.html
https://pypi.python.org/pypi/zeroconf


http://www.ietf.org/rfc/rfc2710.txt
https://tools.ietf.org/html/rfc2710



(239.255.255.250:1900)

