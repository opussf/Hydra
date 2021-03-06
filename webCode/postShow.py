#!/usr/bin/env python
#############
# By opussf
#############
# this script is intnded to be run hourly.
# it will:
# * clear old media files
# * scan sub directories and:
#   - scan cron.txt
#   - if a valid cron entry is found:
#     * Move the oldest media file from src/ to targetDir
#     * Move the associated .ifo file as well


import os, sys
import os.path
import shutil
import time
import datetime
from optparse import OptionParser
import crontab_parser
import logging
import json
import threading
import Queue
import timeit

validTypes = ['m4a','m4v','mp4','mp3','aiff']

movedMessages = []

def copyQueuedFiles():
	logger.debug( "Starting worker." )
	done = False
	while not done:
		( src, dist ) = copyQueue.get( True )
		if len( src ) == 0:
			logger.debug( "Enpty src found.  Time to end thread." )
			done = True
			copyQueue.task_done()
			break

		logger.info( "Process: %s" % ( src, ) )
		logger.debug( "Path to check diskfree: %s" % ( os.path.dirname( dist ) ) )
		st = os.statvfs( os.path.dirname( dist ) )
		diskFree = st.f_bavail * st.f_frsize
		fileSize = os.path.getsize( src )
		logger.debug( "Filesize: %i, diskFree: %i" % ( fileSize, diskFree ) )
		logger.info( "File is %0.02f%% of the diskFree." % ( (fileSize * 1.0 / diskFree ) * 100, ) )
		if diskFree > fileSize:
			start = timeit.default_timer()
			if not dryrun:
				try:
					shutil.copy( src, dist )
					os.remove( src )
				except OSError as err:
					logger.error( "OS error: %s" % ( err, ) )
			else:
				time.sleep( 2.2 )
			end = timeit.default_timer()
			movedMessages.append( "Moved%s (in %00.03fs) ---> %s" %
					( dryrun and " (dryrun)" or "", end-start, dist ) )
		else:
			logger.error( "Diskfree: %i < Filesize: %i. Not enough space to post file." %
					( diskFree, fileSize ) )
		copyQueue.task_done()
	logger.debug( "Ending worker." )

def postFiles( basePath ):
	"""This posts files to basePath from basePath/src
	Posts the oldest media file
	"""
	srcDir = os.path.join(basePath, "src")
	logger.debug("Searching %s" % (srcDir, ))
	allFiles = os.listdir(srcDir)  # file list
	allFiles = map(lambda x: x.split(os.extsep), allFiles)  #split by extsep
	allFiles = map(lambda x: [os.extsep.join(x[:-1]), x[-1]], allFiles)  # first part joined  [name, ext]
	allFiles = map(lambda x: [x[0], x[1], os.lstat(os.path.join(srcDir, os.extsep.join(x))).st_mtime], allFiles)
	allFiles = sorted(allFiles, key=lambda k: k[2]) # sort the files by modtime
	allFiles = map(lambda x: x[:-1], allFiles) #remove the modtime element

	# files that match the expected extension
	validFiles = filter(lambda x: x[-1] in validTypes, allFiles)
	logger.debug("Valid File count: %i" % (len(validFiles),) )

	# base names of files
	nameFiles = map(lambda x: os.extsep.join(x[:-1]), validFiles)

	logger.debug("%s has %i files" % (srcDir, len(nameFiles)))

	if len(nameFiles):
		moveFile = nameFiles[0]
		logger.info("Posting: %s" % (moveFile,))

		moveFiles = filter(lambda x: x[0] == moveFile, allFiles)

		moveFiles = map(lambda x: os.extsep.join(x), moveFiles)

		for file in moveFiles:
			src = os.path.join(srcDir, file)
			dist = os.path.join(basePath, file)
			logger.info( "Queuing: %s" % (src,))
			copyQueue.put( (src, dist) )

def pruneFiles( basePath ):
	pruneFiles = os.listdir(basePath)
	pruneFiles = filter(lambda x: not os.path.isdir(x), pruneFiles) # ignore dirs

	# split into basefilename and extension
	pruneFiles = map(lambda x: x.split(os.extsep), pruneFiles)
	pruneFiles = map(lambda x: [os.extsep.join(x[:-1]), x[-1]], pruneFiles)

	# only prune file types that are valid (media files)
	validFiles = filter(lambda x: x[-1] in validTypes, pruneFiles)

	# only the base filenames
	nameFiles = map(lambda x: os.extsep.join( x[:-1]), validFiles)

	# find the files (media and .ifo) that are valid to delete
	testFiles = filter(lambda x: x[0] in nameFiles, pruneFiles)

	# join the filenames back together
	testFiles = map(lambda x: os.extsep.join(x), testFiles)

	# join with timestamps of the file
	# [( 37342, "file")]
	timestamped = map( lambda x: (os.lstat(os.path.join(basePath,x)).st_mtime, x), testFiles )

	# filter the list down to those within the pruneReportAge
	timestamped = filter( lambda x: x[0] - cutofftime < pruneReportAge, timestamped )
	timestamped.sort()

	logger.debug( "Old files to report on: %i" % (len(timestamped),) )

	# walk through this list, report / delete
	cutoffDT = datetime.datetime.fromtimestamp( cutofftime )
	for f in timestamped:
		thisfile = os.path.join( basePath, f[1] )
		diftime = datetime.datetime.fromtimestamp(f[0]) - cutoffDT
		diftimeStr = "%s" % (diftime,)
		if( f[0] < cutofftime ):
			try:
				logger.debug( "Delete: %s" % ( thisfile, ) )
				if not dryrun:
					logger.info( "Deleted <--- %s" % ( f[1], ) )
					os.remove( thisfile )
				else:
					logger.info( "Deleting (dryrun): %s" % ( f[1], ) )
			except OSError, (errno, strerror):
				logger.error("OSError(%s): %s" % (errno, strerror))
		elif( f[0] - cutofftime < pruneReportAge ):
			logger.info("Remove: in %16s: %s" % ( diftimeStr[:-7], f[1] ) )

def warnFiles( basePath ):
	# return posted, queued files
	logger.debug("Warn started for %s" % (basePath,) )
	postedFiles = os.listdir(basePath)
	postedFiles = filter(lambda x: not os.path.isdir(x), postedFiles )
	# split into basefilename and extenstion
	postedFiles = map( lambda x: x.split(os.extsep), postedFiles )
	# shorten to only 2 entries
	postedFiles = map( lambda x: [os.extsep.join(x[:-1]), x[-1]], postedFiles )
	postedFiles = filter( lambda x: x[-1] in validTypes, postedFiles )
	postedFiles = len(postedFiles)

	toPostFiles = os.listdir( os.path.join( basePath, "src" ) )
	toPostFiles = map( lambda x: x.split(os.extsep), toPostFiles )
	toPostFiles = filter( lambda x: x[-1] in validTypes, toPostFiles )
	toPostFiles = len( toPostFiles )

	infoLine = "Status: %3i posted, %3i queued." % ( postedFiles, toPostFiles )
	warningLine = ( postedFiles == 0 and "NO FILES CURRENTLY POSTED. " or "" ) + \
			( toPostFiles <= 5 and "THE QUEUE IS %s" % ( toPostFiles == 0 and "EMPTY" or "SMALL" ) or "" )

	if ( postedFiles + toPostFiles <= 5 ):
		logger.warning( "%s %s" % ( infoLine, warningLine ) )
	else:
		logger.info( "%s %s" % ( infoLine, warningLine ) )
	return( ( postedFiles, toPostFiles ) )

def futureFiles( basePath, daysInFuture=30 ):
	"""Write what is expected to be posted in the future, to a json file
	Returns the inner list for the show:
	[["name of file | desc name", posting TS, "description"]]
	"""
	logger.debug( "Future scan started for %s" % ( basePath, ) )
	srcDir = os.path.join( basePath, "src" )

	returnList = []

	# list all files
	allFiles = os.listdir( srcDir )
	allFiles = map(lambda x: x.split(os.extsep), allFiles) #split by extsep
	allFiles = map(lambda x: [os.extsep.join(x[:-1]), x[-1]], allFiles) #first part joined [name, ext]
	allFiles = map(lambda x: [x[0], x[1], os.lstat(os.path.join( srcDir, os.extsep.join(x))).st_mtime], allFiles) # add the mtime to the list
	allFiles = sorted(allFiles, key=lambda k: k[2]) # sort the files by modtime
	allFiles = map(lambda x: x[:-1], allFiles) #remove the modtime element

	#filter files that only match the expected extension
	validFiles = filter(lambda x: x[-1] in validTypes, allFiles)

	# base names of files
	nameFiles = map(lambda x: os.extsep.join(x[:-1]), validFiles)

	# look for cronfile
	if os.path.exists( os.path.join( basePath, cronFile ) ):
		logger.debug( "Found cron file" )
		cronLines = open( os.path.join( basePath, cronFile ), "r" ).readlines()
		cronLines = map( lambda x: x.strip(), cronLines )

		baseTime=datetime.datetime.now()

		for fileName in nameFiles:  # Loop through the filenames
			minTimes = [] #Collect all of the possible times from the file.
			for line in cronLines:
				line = line.strip()
				logger.debug( "Parse %s" % ( line, ) )

				try:
					cronCheck.set_value(line)
					nextTime = cronCheck.next_run(baseTime)
					minTimes.append( nextTime )
				except ValueError:
					logger.error( "Remove bad cron `%s`" % ( line, ) )
					cronLines.remove( line )

				#if nextTime < baseTime:
				#	nextTime += datetime.timedelta( days=31 )

			postTime = min( minTimes ) # next post time is the min val of the list

			returnList.append( [fileName, "%s" % ((postTime.strftime("%s"),)), "desc", "%s" % (postTime,)] ) # fix this by finding the file and the desc file
			baseTime = postTime + datetime.timedelta( hours=1 ) # add an hour to the post time, since it is run once an hour

			logger.debug( "%s (%s) %s" % (postTime, baseTime, fileName) )  # print for debugging

	return returnList


runInPath = os.path.abspath(os.path.dirname(sys.argv[0]))

parser = OptionParser()
parser.add_option("-d", "--dryrun", action="store_false", dest="dryrun", default=True,
		help="disable the default dryrun. Actually perform actions.")
parser.add_option("-a", "--age", action="store", dest="daysback", type="int", default=7*13,  # 16 weeks
		help="set the age of files to clean up")
parser.add_option("-r", "--root", action="store", dest="rootDir", type="string", default=runInPath,
		help="set the root directory to scan")
parser.add_option("-c", "--cronfile", action="store", dest="cronFile", type="string", default="cron.txt",
		help="set the name of the cronfile to parse")
parser.add_option("-v", "--verbose", action="store_true", dest="verbose", default=False,
		help="turn on debug output")
parser.add_option("", "--next", action="store_true", dest="showNext", default=False,
		help="show next posting")
parser.add_option("-j", "--json", action="store", dest="jsonFile", type="string", default="future.json",
		help="set the file to dump information about future postings to.")

(options, args) = parser.parse_args()

dryrun = options.dryrun
daysback = options.daysback
cronFile = options.cronFile
jsonFile = options.jsonFile

logger = logging.getLogger("postShows")
logger.setLevel(logging.DEBUG)
sh = logging.StreamHandler()
sh.setLevel(options.verbose and logging.DEBUG or logging.INFO)

formatter = logging.Formatter('%(asctime)s %(levelname)s %(message)s')
sh.setFormatter(formatter)
logger.addHandler(sh)

logger.info("Starting")
if dryrun:
	logger.info("Dryrun engaged (use -d to disable dry run)")

# https://docs.python.org/2/library/queue.html#module-Queue

copyQueue = Queue.Queue()
logger.debug( "Queue object created." )

copyThread = threading.Thread( target=copyQueuedFiles )
copyThread.start()


# pruneReportAge controls how far into the future
# to start reporting when a file is about to be
# removed. In seconds
pruneReportAge = 7*24*3600

cutofftime = time.time() - (3600 * 24 * daysback) - 240  # fudge factor
cutofftime = time.time() - (3600 * 24 * daysback) + 240
logger.debug("Cutoff Time %s" % time.ctime(cutofftime) )

# init the future dataStructure
#{"showName": [ "Filename", "TS to post", "desc contents"],}
future = {}

# init the cronCheck object
cronCheck = crontab_parser.SimpleCrontabEntry()
totalPosted, totalQueued = 0, 0

for root, dirs, files in os.walk( options.rootDir ):
	dirs.sort( reverse=False )
	if dirs == ['src']:  # only process a dir with the 'src' subdir
		show = os.path.split(root)[-1:][0]
		logger.info("Checking: %s" % show)
		if cronFile in files: # only post files if the cronFile exists
			# loop through all the lines in the cronFile, post on the first match only.
			for line in open(os.path.join(root,cronFile), 'r'):
				line = line.strip()
				try:
					logger.debug(">%s< is being checked" % line)
					cronCheck.set_value(line)
				except:
					logger.error("%s invalid cron syntax, ignoring" % line)
					continue
				if cronCheck.matches():
					logger.debug("\tMatched")
					postFiles(root)
					break
				else:
					logger.debug("Next run at: %s" % (cronCheck.next_run(),))

		pruneFiles(root) # Always try to prune files
		posted, queued = warnFiles(root) # generate any warnings
		future[show]=futureFiles(root) # add future files to json structure
		totalPosted = totalPosted + posted
		totalQueued = totalQueued + queued

logger.info( "Writing future data to: %s" % ( jsonFile, ) )
logger.info( "Totals: %4i posted, %4i queued, %5i total." %
		( totalPosted, totalQueued, totalPosted + totalQueued ) )

#import pprint
#pprint.pprint(future)

file( os.path.join( options.rootDir, jsonFile ), "w" ).write( json.dumps( future ) )
logger.debug( "Copy queue size is: %i" % ( copyQueue.qsize(), ) )
logger.debug( "Adding empty control item to queue." )
copyQueue.put( ( "", "" ) )
logger.info( "Blocking until copy queue is done" )
copyQueue.join()

for msg in movedMessages:
	logger.info( msg )

logger.info("Completed")
