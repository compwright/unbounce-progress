<?php

// EMBED STYLE

$bgColor = '#e4e4e4';
$barColor = 'repeating-linear-gradient(-45deg, #01426A, #01426A 10px, #F5F5F5 10px, #F5F5F5 20px)';
$font = 'Arial, Helvetica, sans-serif';
$fontSize = '20px';
$fontColor = '#01426A';


// CONFIGURATION

$startWith = 50; // prime the pump with a number of pretend signers


// API SETTINGS

$unbounce_api_key = 'INSERT_API_KEY';
$unbounce_page_id = 'INSERT_PAGE_ID';


// API (DO NOT EDIT BELOW THIS LINE)

if (!empty($_GET['api'])) {
	try {
		$cache_ttl = 30;
		$cache_dir = sys_get_temp_dir();
		$cache_file = $cache_dir . DIRECTORY_SEPARATOR . $unbounce_page_id . '.json';

		$body = cache_get($cache_file, $cache_ttl);
		if (!$body) {
			$url = "https://api.unbounce.com/pages/$unbounce_page_id/leads?count=true";
			$body = api_get($url, $unbounce_api_key);
			cache_put($cache_file, $body);
		}

		try {
			$response = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
			$count = $response->metadata->count + $startWith;
			$goal = goal($count);
		} catch (Exception $e) {
			throw new Exception("Error parsing response", 0, $e);
		}
	} catch (Exception $e) {
		header('Content-Type: application/json');
		die(json_encode(['error' => $e->getMessage()]));
	}

	header('Content-Type: application/json');
	die(json_encode(compact('count', 'goal')));
}

function cache_get($file, $ttl = 30) {
	return file_exists($file) && time() - filemtime($file) <= $ttl
		? file_get_contents($file)
		: false;
}

function cache_put($file, $data) {
	file_put_contents($file, $data, LOCK_EX);
}

function api_get($url, $unbounce_api_key) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
	curl_setopt($ch, CURLOPT_USERPWD, "$unbounce_api_key:");
	$body = curl_exec($ch);
	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	if (curl_errno($ch)) {
		throw new Exception(curl_error($ch));
	} elseif ($status > 200) {
		throw new Exception("HTTP $status: $body");
	}
	curl_close ($ch);

	return $body;
}

function goal($count) {
	for ($p = 100;; $p *= 10) {
		foreach ([1, 2, 5] as $n) {
			// creates a series of numbers of increasing exponent,
			// i.e. 100, 200, 500, 1000, 2000, 5000, 10000, ...
			$goal = $p * $n;
			if ($count < $goal) {
				return $goal;
			}
		}
	}
}


// EMBED

?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>Unbounce Progress Bar</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style type="text/css">
    html, body {
      margin: 0;
      padding: 0;
      width: 100%;
      height: 100%;
    }
    hr, p {
      border: none;
      display: block;
      margin: 0;
      padding: 0;
    }

    .progress {
      position: relative;
      box-sizing: border-box;
      border-radius: 4px;
      padding-top: 20px;
    }
    .progress::before, .progress::after {
      display: inline-block;
      position: absolute;
      z-index: 3;
      vertical-align: middle;
      font-size: 60%;
      padding: 0;
      top: 0;
      line-height: 24px;
      opacity: 0.5;
    }
    .progress::before {
      content: attr(start);
      left: 0;
    }
    .progress::after {
      content: attr(end);
      right: 0;
    }
    .progress-bar-track {
      height: 24px;
      border-radius: 4px;
      overflow: hidden;
    }
    .progress-bar {
      width: 0%;
      height: 100%;
      transition: width 1s;
    }
    .progress-bar-caption {
      margin-top: 0.33em;
      text-align: left;
      white-space: nowrap;
    }

    /* theming */
    body {
      font-family: <?php echo $font; ?>;
      font-size: <?php echo $fontSize; ?>;
      color: <?php echo $fontColor; ?>;
    }
    .progress-bar-track { background: <?php echo $bgColor; ?>; }
    .progress-bar { background: <?php echo $barColor; ?>; }

    /* responsiveness */
    @media screen and (max-width: 414px) {
      #app {
        font-size: 80%;
      }
    }
  </style>

  <script src="https://cdn.jsdelivr.net/npm/vue"></script>
</head>

<body>
  <div id="app">
    <div class="progress" start="0" v-bind:end="Number(goal).toLocaleString()">
      <div class="progress-bar-track">
        <hr class="progress-bar" v-bind:style="progress" />
      </div>
    </div>
    <p class="progress-bar-caption">
      <b>{{Number(count).toLocaleString()}} signatures</b> &ndash; help us get to
      {{Number(goal).toLocaleString()}}!
    </p>
  </div>
  <script type="text/javascript">
    var app = new Vue({
      el: '#app',
      data: {
        count: 0,
        goal: 100,
        progress: {
          width: '0%'
        }
      },
      mounted() {
        var data = this;
        function refresh() {
          fetch('?api=1').then(function(res) {
            return res.json();
          }).then(function(res) {
            data.count = res.count;
            data.goal = res.goal;
            data.progress.width = Math.round(100 * res.count / res.goal) + '%';
          });
        }
        refresh();
        setInterval(refresh, 30*1000);
      }
    });
  </script>
</body>

</html>
