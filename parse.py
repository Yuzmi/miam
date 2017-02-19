# https://docs.python.org/3/howto/argparse.html
import argparse;

# https://docs.python.org/3/library/subprocess.html
import subprocess;

# https://docs.python.org/3/library/threading.html
import threading;

def getFeeds(args=[]):
	result = subprocess.run(
		args = ['php', 'bin/console', 'miam:get:feeds'] + args,
		stdout = subprocess.PIPE,
		universal_newlines = True
	)
	
	return result.stdout.splitlines()


def parseFeed(feed, args=[]):
	feed = feed.split()
	feedId = int(feed[0])

	result = subprocess.run(
		args = ['php', 'bin/console', 'miam:parse:feeds', str(feedId)] + args,
		stdout = subprocess.PIPE,
		universal_newlines = True
	)

	print(str(feedId)+" : "+str(result.stdout.rstrip('\n'))+" - "+feed[1])

	return result.returncode


def parseFeedAfterFeed(feeds, args=[]):
	while feeds:
		feed = feeds.pop(0)
		parseFeed(feed, args)

# Threads
countThreads = 8

# Available arguments
parser = argparse.ArgumentParser()
parser.add_argument("--env")
parser.add_argument("--feeds")
parser.add_argument("--ignore-invalid", action="store_true")
parser.add_argument("--no-cache", action="store_true")
parser.add_argument("--no-debug", action="store_true")
parser.add_argument("--threads")
parser.add_argument("--timeout")

# Parse arguments
args = parser.parse_args()
newGetArgs = []
newParseArgs = []

if args.env == 'prod':
	newGetArgs.append("--env=prod")
	newParseArgs.append("--env=prod")
else:
	newGetArgs.append("--env=dev")
	newParseArgs.append("--env=dev")

if args.feeds:
	newGetArgs.append(args.feeds)

if args.ignore_invalid:
	newParseArgs.append("--ignore-invalid")

if args.no_cache:
	newParseArgs.append("--no-cache")

if args.no_debug:
	newGetArgs.append("--no-debug")
	newParseArgs.append("--no-debug")

if args.threads:
	threads = int(args.threads)
	if threads > 0:
		countThreads = threads

if args.timeout:
	timeout = int(args.timeout)
	if timeout > 0:
		newParseArgs.append("--timeout="+str(timeout))

# Get feeds
feeds = getFeeds(newGetArgs)

# Parse feeds in threads
for i in range(0, countThreads):
	t = threading.Thread(
		target = parseFeedAfterFeed,
		args = [feeds, newParseArgs]
	)
	#t.daemon = True
	t.start()
