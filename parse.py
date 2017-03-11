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
countThreads = 2
scriptDir = os.path.dirname(os.path.abspath(__file__))
timeout = 30
verbose = False

# Available arguments
parser = argparse.ArgumentParser()
parser.add_argument("-e", "--env")
parser.add_argument("--feeds")
parser.add_argument("--ignore-invalid", action="store_true")
parser.add_argument("--no-cache", action="store_true")
parser.add_argument("--threads")
parser.add_argument("--timeout")
parser.add_argument("-v", "--verbose", action="store_true")

# Parse arguments
args = parser.parse_args()
getArgs = ["--no-debug"]
parseArgs = ["--no-debug"]

if args.env == 'dev':
	getArgs.append("-e=dev")
	parseArgs.append("-e=dev")
else:
	getArgs.append("-e=prod")
	parseArgs.append("-e=prod")

if args.feeds:
	getArgs.append(args.feeds)

if args.ignore_invalid:
	parseArgs.append("--ignore-invalid")

if args.no_cache:
	parseArgs.append("--no-cache")

if args.threads:
	threads = int(args.threads)
	if threads > 0:
		countThreads = threads

if args.timeout:
	newTimeout = int(args.timeout)
	if newTimeout > 0:
		timeout = newTimeout
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
