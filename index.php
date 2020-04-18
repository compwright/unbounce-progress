<?php

// EMBED STYLE

$bgColor = '#e4e4e4';
$barColor = 'repeating-linear-gradient(-45deg, #00abff, #00abff 10px, #9df 10px, #9df 20px)';
$font = 'sans-serif';
$fontSize = '16px';
$fontColor = '#333333';


// API SETTINGS

$unbounce_api_key = '21190ffef070d44747a046b804e5a116';
$unbounce_page_id = '73948c39-adae-435f-9aad-6290f7b61dba';


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
			$count = $response->metadata->count;
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
      height: 24px;
      margin-top: 20px;
      position: relative;
      box-sizing: border-box;
      border-radius: 4px;
    }
    .progress::before, .progress::after {
      display: inline-block;
      position: absolute;
      z-index: 3;
      vertical-align: middle;
      font-size: 10px;
      padding: 0;
      top: -20px;
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
    .progress-bar {
      position: absolute;
      z-index: 1;
      top: 0;
      left: 0;
      width: 0%;
      height: 100%;
      border-radius: 4px;
      transition: width 1s;
    }
    .progress-bar-caption {
      margin-top: 0.33em;
      text-align: left;
    }

    /* theming */
    body {
      font-family: <?php echo $font; ?>;
      font-size: <?php echo $fontSize; ?>;
      color: <?php echo $fontColor; ?>;
    }
    .progress { background: <?php echo $bgColor; ?>; }
    .progress-bar { background: <?php echo $barColor; ?>; }
  </style>

  <script src="https://cdn.jsdelivr.net/npm/vue"></script>
</head>

<body>
  <div id="app">
    <div class="progress" start="0" v-bind:end="Number(goal).toLocaleString()">
      <hr class="progress-bar" v-bind:style="progress" />
    </div>
    <p class="progress-bar-caption">
      <b>{{Number(count).toLocaleString()}} people</b> have signed - help us get to
      {{Number(goal).toLocaleString()}} signatures!
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
