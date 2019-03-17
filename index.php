<?php

$HOST_PROFILES = array(
	'localhost' => array(
		'user' => 'username',
		'pwd' => 'password',
		'names' => 'utf8'
	),
	
	'remote.mydomain.com' => array(
		'user' => 'username',
		'pwd' => 'password',
		'names' => 'utf8',
		'ssl' => array(
			'key' => NULL,
			'cert' => NULL,
			'ca' => '/etc/ssl/certs/ca-bundle.crt',
			'capath' => NULL,
			'cipher' => NULL
		)
	)
);

$host = @$HOST_PROFILES[$_REQUEST['host']];
$host_name = $host ? $_REQUEST['host'] : false;

if(isset($_REQUEST['db']) && preg_match('/^[^\\\\\/\?%\*:\|\"<>\.]{1,64}$/', $_REQUEST['db']))
	$db = $_REQUEST['db'];
else
	$db = false;


$sql = @$_REQUEST['sql'];
if(!$sql) $sql = 'SELECT * FROM table';
	
?>
<!DOCTYPE html>
<html>
<head>
<title>SQLPlayer<?php if($db && $host) echo ': ' . $db . ' / ' . $host_name; ?></title>
<link rel="stylesheet" href="sqlplayer.css" />
</head>

<script type="text/javascript">
document.onkeydown = function(e) {
	if(e.ctrlKey && e.keyCode==13) {
		e.preventDefault();
		document.getElementById("gobtn").click();
	}
}
</script>

<body>

<form method="post">
<h1>SQLPlayer</h1>

<div id="dbhost">
<input type="text" name="db" value="<?php echo $db; ?>" size="30" placeholder="(database)" />
<select name="host">
<?php
foreach($HOST_PROFILES as $h=>$v)
	echo '<option value="' . $h . '"' . ($h==$host_name ? ' selected' : '') . '>' . $h . '</option>';
?>
</select>
</div>

<label for="sql">Query:</label><br/>
<textarea id="sql" name="sql">
<?php echo $sql; ?>
</textarea>

<div id="go"><span class="tip">Hotkey: Ctrl+Enter &nbsp;</span> <input id="gobtn" class="button" type="submit" value="Go" /></div>
</form>

<?php

function finish() {
?>
<p id="ver">SQLPlayer version 1.1 (Mar 2019)</p>
</body>
</html>
<?php
exit;
}

function error($msg, $more = false) {
	echo '<p class="info"><b>' . $msg . '</b>' . ($more ? '<br/>' . $more : '') . '</p>';
	finish();
}

if(!$db) error('Database not specified.');
if(!$host) error('Unknown host profile.');

$con = @mysqli_init();

if(@is_array($host['ssl'])) {
	$ssl = $host['ssl'];
	@mysqli_options($con, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
	@mysqli_ssl_set($con, @$ssl['key'], @$ssl['cert'], @$ssl['ca'], @$ssl['capath'], @$ssl['cipher']);
}

$con_success = @mysqli_real_connect($con, $host_name, @$host['user'], @$host['pwd'], $db);
if(!$con_success) error('Unable to connect to MySQL.', @mysqli_connect_error());

if(@$host['names']) @mysqli_query($con, "SET NAMES " . $host['names']);

$result = @mysqli_query($con, $sql);
if(!$result) error('SQL error.', @mysqli_error($con));

if($num_rows = @mysqli_num_rows($result)) {
	echo '<div id="data"><table>';
	
	$i = 0;
	while($r = @mysqli_fetch_assoc($result)) {
		if($i==0) {
			echo '<tr class="h">';
			foreach(array_keys($r) as $name) {
				echo '<th>' . $name . '</th>';
			}
			echo '</tr>';
		}
		echo '<tr>';
		foreach($r as $value) {
			echo '<td>' . htmlspecialchars($value) . '</td>';
		}
		echo '</tr>';
		$i++;
		if($i==1000) break;
	}
	@mysqli_free_result($result);
	
	echo '</table></div>';
	echo '<p class="info">' . ($i>=1000 ? 'Showing 1000 of ' . $num_rows . ' records.' : $num_rows . ' records total.') . '</p>';
}
else {
	echo '<p class="info">No records returned.</p>';
}

finish();
?>
