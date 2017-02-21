import argparse; # https://docs.python.org/3/howto/argparse.html
import os;
import subprocess; # https://docs.python.org/3/library/subprocess.html
import threading; # https://docs.python.org/3/library/threading.html

def parseFeedAfterFeed(feeds, scriptDir, args=[], verbose=False):
	while feeds:
		feed = feeds.pop(0).split()
		feedId = int(feed[0])

		result = subprocess.run(
			args = ['php', scriptDir+'/bin/console', 'miam:parse:feeds', str(feedId)] + args,
			stdout = subprocess.PIPE,
			universal_newlines = True
		)
		
		if verbose:
			print(str(feedId)+" : "+str(result.stdout.rstrip('\n'))+" - "+feed[1])


# Init
countThreads = 4
scriptDir = os.path.dirname(os.path.abspath(__file__))
verbose = False

# Available arguments
parser = argparse.ArgumentParser()
parser.add_argument("--env")
parser.add_argument("--feeds")
parser.add_argument("--ignore-invalid", action="store_true")
parser.add_argument("--no-cache", action="store_true")
parser.add_argument("--no-debug", action="store_true")
parser.add_argument("--threads")
parser.add_argument("--timeout")
parser.add_argument("--verbose", action="store_true")

# Parse arguments
args = parser.parse_args()
getArgs = []
parseArgs = []

if args.env == 'prod':
	getArgs.append("--env=prod")
	parseArgs.append("--env=prod")
else:
	getArgs.append("--env=dev")
	parseArgs.append("--env=dev")

if args.feeds:
	getArgs.append(args.feeds)

if args.ignore_invalid:
	parseArgs.append("--ignore-invalid")

if args.no_cache:
	parseArgs.append("--no-cache")

if args.no_debug:
	getArgs.append("--no-debug")
	parseArgs.append("--no-debug")

if args.threads:
	threads = int(args.threads)
	if threads > 0:
		countThreads = threads

if args.timeout:
	timeout = int(args.timeout)
	if timeout > 0:
		parseArgs.append("--timeout="+str(timeout))

if args.verbose:
	verbose = True

# Get feeds
getFeeds = subprocess.run(
	args = ['php', scriptDir+'/bin/console', 'miam:get:feeds'] + getArgs,
	stdout = subprocess.PIPE,
	universal_newlines = True
)
feeds = getFeeds.stdout.splitlines()

# Parse feeds in threads
for i in range(0, countThreads):
	t = threading.Thread(
		target = parseFeedAfterFeed,
		args = [feeds, scriptDir, parseArgs, verbose]
	)
	t.start()
