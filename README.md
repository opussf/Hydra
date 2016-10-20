# Hydra
RSS feed system supporting multiple heads.

## Setup
Create a place you want to serve content from.
A path on a web server.

Secure this if you want.
I use http auth through Apache2 configuration.

Decide how often you want to run the ```postShow.py``` script.
I run it once an hour at 10 minutes past the hour.

```10 * * * * <PATH_TO_HYDRA>/postShow.py -d >> <YOUR_LOG_FILENAME> 2>&1```

Running ```postShow.py``` without the -d runs in dryrun mode.
Log contents will be created, no changes will be made.

By default, this will auto remove posted files after 13 weeks.
Either edit the script to change that, or pass the -a, --age parameter when you run it to change it.

## Content
To post content, create a directory with this structure:

```
.
`-- <folder>
	|-- cron.txt
	`-- src
```

Put any number of cron patterns in cron.txt.
If any of them match, the oldest file in src will be copied to <folder>.

Copy the content into the src folder, and order it with modification date.

Note: if you copy files into src/ one at a time, the modification date will reflect your actions.

### cron.txt
If you are running this once an hour, I recommend leaving the cron pattern minute field as *.

IE ```* 10 * * *```  -- What ever minute the job runs, matches 10 am.

With lots of content, the ```postShow.py``` may take a while to copy all the files, and matches may try to happen up to a few miuntes later.
If it is scheduled to run more than once an hour, you will want to give a range of values for the minute field.

IE ```15-29 10 * * *``` -- If you run it on the quarter hour, this will give it a 14 minute window, and only publish at about the 2nd quarter.

### Supported file types.
This currently only supports .m4v, .mp4, and .mp3 file extensions.
Feel free to expand the script to support your types, remember to make sure that RSS will support the new formats.

### Support files.
For each content file (.m4v, .mp4, or .mp3), there can be an .ifo file.
(Replace the .m4v, .mp4 or .mp3 with .ifo.)
.ifo files will get moved, and should be removed along with their corrisponding content file.

The .ifo file is a 2 line text file.
The first line will be used for the title of the RSS item (defaults to the path/filename).
The second and more lines will be published in the RSS item description (defaults to a short string).


