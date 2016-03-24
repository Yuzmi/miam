var request = require('request').defaults({ encoding: 'utf8', timeout: 10000 });
var fs = require('fs');

function getFeeds() {
	var file = __dirname+'/feeds.json';

	fs.readFile(file, function(error, data) {
		if(error) throw error;

		var feeds = JSON.parse(data);

		for(var i = 0; i < feeds.length; i++) {
			//getFeed(feeds[i]);
			setTimeout(getFeed, i*250, feeds[i]);
		}
	});
}

function getFeed(feed) {
	request({
		method: "GET",
		uri: feed.url
	}, function(error, response, body) {
		if(!error && response.statusCode == 200) {
			console.log(feed.id+" - Récupéré - "+body.length);
			fs.writeFile(__dirname+"/rss/"+feed.id+".rss", body, function(error, response) {
				if(error) {
					console.log(feed.id+" - Erreur lors de l'écriture");
				}
			});
		} else if(!error) {
			console.log(feed.id+" - "+response.statusCode+" - "+feed.url);
		} else {
			console.log(feed.id+" - Erreur - "+feed.url);
		}
	});
}

getFeeds();