# Unbounce Progress Bar Widget

A simple progress bar inspired by MoveOn.org for petition pages created with Unbounce. Automatically refreshes every 30 seconds and loads with a smooth animation.

![](https://www.dropbox.com/s/snkh2evt0a2fx4a/Screenshot%202020-04-17%2020.34.01.png?raw=1)

## Requirements

* Web server running PHP
* Unbounce account and API key

## Installation

1. Download the [index.php](index.php) file, and edit the [Unbounce API key](https://developer.unbounce.com/getting_started/#api-keys) and page ID
2. Upload the index.php file to your web server
3. Embed the widget embed code into your Unbounce landing page:

```
<iframe src="http://your-web-server.com/index.php" style="width:100%;max-width:600px;height:75px;border:none"></iframe>
```

Customize the `src` URL to point to the address of the index.php file which you uploaded.
