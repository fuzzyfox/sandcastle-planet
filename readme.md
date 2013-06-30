# SandCastle - Planet
SandCastle is a collection of tools for creating a community website/portal. It is currently under heavy development, kickstarted by the [mozilla.org.uk][1] website revamp in 2012. Built ontop of the [CodeIgniter][2] for ease of use, however each componenet should provide a good starting point for those wishing to use another framework (or even pure PHP).

This [Spark][3] is the beginings of a planet system (feed aggregator). It provides a library of functions to ease in the parsing of RSS, though at a much simpler level than larger frameworks such as [SimplePie][4]. If you need fine grain control then this isn't the tool for you. If you just want to quickly aggregate some content then this will do the job well.

I'm going to assume that you're using [CodeIgniter][2], and thus you have a basic understanding of PHP. These are both prerequisites if you want to follow the instructions from now on.

## Installation
First you will need to install the [Spark Package Manager][5]. Once you have move into the root of your CodeIgniter application and issue the following command:

	php tools/spark install sandcastle-planet 

To load the library (and supporting database model) into your application use the following, replacing `x.x.x` with a version number (see [tags][6]).

	$this->load->spark('sandcastle-planet/x.x.x');

The main library will now be available using `$this->planet` however if you wish to use the model as well then that can be loaded with:

	$this->load->model('planet_model');

## Usage
### $this->planet->get_feed($url)
Gets feed(s) from the site provided to the function using the SimpleXML php extension. This function works recursively to get feeds if an array of urls is passed in. *Returns a feed object sorted by date/time, or FALSE if feed is not found/supported.*

#### Usage:
	
	// single feed
	$feed = $this->planet->get_feed('http://www.example.com/feed.rss');
	
    // multiple feeds
    $feeds = $this->planet->get_feed(array(
        'http://www.example.com/feed.rss',
        'http://www.anotherexample.com/feed.rss'
    ));

### $this->planet->get_feed_title($url)
Gets the title of a feed (i.e. it's name) from it's URL. *Returns a string, or FALSE if the feed is not found/supported.*

#### Usage:

	$feed_title = $this->planet->get_feed_title('http://www.example.com/feed.rss');

### $this->planet->valid_feed($url)
Validates that a feed exists, and is supported by this library. *Returns TRUE on success.*

#### Usage:

	if($this->planet->valid_feed('http://www.example.com/feed.rss')){
		echo 'Valid Feed';
	}
	else {
		echo 'Invalid Feed';
	}

## License
### CodeIgniter
For more information on the CodeIgniter License read it over at [http://ellislab.com/codeigniter/user-guide/license.html][7].

### SandCastle
This Source Code Form is subject to the terms of the Mozilla Public
License, v. 2.0. If a copy of the MPL was not distributed with this file,
You can obtain one at [mozilla.org/MPL/2.0][8].

[1]: http://www.mozilla.org.uk/
[2]: http://ellislab.com/codeigniter/
[3]: http://getsparks.org/
[4]: http://simplepie.org/
[5]: http://getsparks.org/install/
[6]: https://github.com/fuzzyfox/sandcastle-planet/tags
[7]: http://ellislab.com/codeigniter/user-guide/license.html
[8]: http://mozilla.org/MPL/2.0/