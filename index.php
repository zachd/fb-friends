<?php
// Errors
error_reporting(0);

// Facebook code
// Requires Facebook PHP SDK
require '../src/facebook.php';
$config = array(
'appId'  => 'APP_ID',
'secret' => 'APP_SECRET',);
$facebook = new Facebook($config);
$user = $facebook->getUser();

// Default functions
function fixem($a, $b){
  if ($a["compatibility"] == $b["compatibility"])
    return 0;
  return ($a["compatibility"] < $b["compatibility"]) ? -1 : 1;
}
?>
<html>
<head> 
<title>Friends Counter</title>
<link rel="icon" type="image/png" href="https://fbstatic-a.akamaihd.net/rsrc.php/yl/r/H3nktOa7ZMg.ico">
<style type="text/css">
* {
  text-decoration:none;
  font-family: Helvetica, Arial, sans-serif;
}
h1 {
  margin-bottom: 0px;
}
.graphlink {
  color: blue;
  cursor: pointer;
}
.graphselected {
  color: black;
  font-weight: bold;
  cursor: inherit;
}
</style>
<script type="text/javascript">
    function togglevis(id, two, three) {
        var e = document.getElementById(id);
        var ebutt = document.getElementById(id + 'butt');
        document.getElementById(two).style.display = 'none';
        document.getElementById(three).style.display = 'none';
        document.getElementById(two + 'butt').className = 'graphlink';
        document.getElementById(three + 'butt').className = 'graphlink';
        if (e.style.display == 'block') {
            e.style.display = 'none';
            ebutt.className = 'graphlink';
        } else {
            e.style.display = 'block';
            ebutt.className = 'graphselected';
        }
    }
</script>
<meta property="og:title" content="Friends Counter" />
<meta property="og:site_name" content="Friends Counter"/>
<meta property="fb:admins" content="1434685963"/>
<meta property="og:type" content="website"/>
<meta property="og:url" content="http://zach.ie/fb/friends/" />
<meta property="og:image" content="http://zach.ie/fb/fb.png" />
<meta name="viewport" content="width=480; initial-scale=0.6666; maximum-scale=1.0; minimum-scale=0.6666" />
<meta property="og:description" content="Sort Facebook friends by mutual connections, and display some interesting statistics about friends list similarities and gender distribution." />
</head>
<body>
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=361103244003981";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<h1><img src="http://zach.ie/fb/friends/friends.png" style="vertical-align:middle;height:50px;width:50px;" alt="App"/> Friends Counter</h1>
<i>Looking for the Messages Counter? Click <a href="/fb/messages/" target="_blank" title="Messages Counter">here</a>.</i><br />
<?php
try {
  // Check if user exists
  if($user) {
      $currentuser = $facebook->api('/me');
      $access_token = $facebook->getAccessToken();
  }
  // Show if not logged in
  if(!$user){
    echo "<br /><b>App information:</b><br />";
    echo "This application connects to Facebook and asks for information on your friends list.";
    echo "<br />Facebook requires this application to request <i>basic read permissions</i> from the user.<br />";
    echo "After you authenticate, friend statistics and friends sorted by mutual connections will be displayed.<br />";
      
  }

  // We have a user ID, so probably a logged in user.
  // If not, we'll get an exception, which we handle below.
  if($user) {
    //Show user photo ?>
    <h2>Hey, <img src="https://graph.facebook.com/<?php echo $currentuser['id']; ?>/picture" style="width: 35px; height: 35px; vertical-align: middle;" /> <?php echo $currentuser['name']; ?>! <br />
    </h2>

    <?php 
    // Query facebook 
    $fql = 'https://graph.facebook.com/fql?q=SELECT+uid1+FROM+friend+WHERE+uid2+=+'.$currentuser['id'];
    $fql = $fql.'&access_token=' . $access_token;
    $result = file_get_contents($fql);
    $result = json_decode($result, true);
    $friendcount = count($result['data']);
    
    // Construct array of all participants
    for($i = 0; $i < $friendcount; $i++)
      $allparticipants[$i]['user_id'] = $result['data'][$i]['uid1'];
      
    // Do it in parts as the friendcount might be big
    $resultfriends = array();
    for($j = 0; $j < $friendcount; $j += 200){
      // Get Participants sexes from facebook into resultarray[]
      $fqlquerypart = 'https://graph.facebook.com/fql?q={';
      for($i = $j, $c = 0; $i < (($j + 200) > $friendcount ? $j + ($friendcount - $j) : $j + 200); $i++, $c++)
        $fqlquerypart = $fqlquerypart .'"query'.$c.'":"SELECT+name,friend_count,wall_count,mutual_friend_count,sex,uid+FROM+user+WHERE+uid+=+\''.$allparticipants[$i]['user_id'].'\'",';
      $fqlquerypart = substr($fqlquerypart , 0, -1).'}&access_token=' . $access_token;
      $resultfriendsretrieved = file_get_contents($fqlquerypart);
      $resultfriendsretrieved = json_decode($resultfriendsretrieved, true);
    if($j == 0)
      $resultfriends = $resultfriendsretrieved['data'];
    else
      $resultfriends = array_merge($resultfriends, $resultfriendsretrieved['data']);
    }
    $resultfriendstoadd = json_encode($resultfriends);
  }
      
    // Counter variables
    $friendsfriends = 0;
    $friendsmutualfriends = 0;
    $friendswallposts = 0;
    $females = 0;
    $males = 0;
    $others = 0;
    
    // Iterate through threads and add to males, females, other, friendcount, unreadcount, percentage
    for($i = 0; $i < $friendcount; $i++){
      $userdata = $resultfriends[$i]['fql_result_set'][0];
      unset($resultfriends[$i]['name']);

      // Add to sex counters
      if($userdata['sex'] == "male")
        $males++;
      else if($userdata['sex'] == "female")
        $females++;
      else
        $others++;

      // Add to other counters
      $friendsfriends += $userdata['friend_count'];
      $friendsmutualfriends += $userdata['mutual_friend_count'];
      $friendswallposts += $userdata['wall_count'];
      $resultfriends[$i]['fql_result_set'][0]['compatibility'] = ($userdata['mutual_friend_count'] / $friendcount) * 100;
      $cleanresults[$i] = $resultfriends[$i]['fql_result_set'][0];
    }

    // Sort Array
    usort($cleanresults, function ($a, $b) { return $b['compatibility'] > $a['compatibility']; });
    
    // Info box
    echo "<b>App information (<a href=\"".$facebook->getLogoutUrl(array('next' => 'http://zach.ie/fb/?logout='.$currentuser['first_name']))."\" onclick=\"FB.logout();\">logout</a>):</b><br />";
    echo "This application displays your Facebook friends list sorted by mutual friends.<br />"; 
    
    // User output
    echo "<br /><b>Friend Statistics:</b><br />";
    $friendsfriendsavg = round($friendsfriends / $friendcount);
    echo "Your friends have on average <b>".number_format($friendsfriendsavg)."</b> friends. You have <b>".number_format($friendcount)."</b> friends ";
    echo "(<b>".($friendsfriendsavg - $friendcount > 0 ? number_format((($friendsfriendsavg - $friendcount) / $friendsfriendsavg) * 100, 1)."%</b> below":number_format((($friendcount - $friendsfriendsavg) / $friendcount) * 100, 1)."%</b> above")." average).<br />";
    echo "You have ";
    if($males != 0)
      echo "<b>".number_format($males)."</b> male friends (<b>".number_format($males/($males + $females + $others) * 100, 1)."%</b>)";
    if($females != 0)
      echo ", <b>".number_format($females)."</b> female friends (<b>".number_format($females/($males + $females + $others) * 100, 1)."%</b>)";
    if($others != 0)
      echo ", and <b>".number_format($others)."</b> others (<b>".number_format($others/($males + $females + $others) * 100, 1)."%</b>)";
    echo ".<br />";
    echo "Your friends have <b>".number_format($friendswallposts)."</b> wall posts. Your friends are connected to <b>".number_format($friendsfriends)."</b> people. <br />";
  
    // Header
    echo "<h3>You have <i>".number_format($friendcount)."</i> friends on facebook";
    echo " (sorted by <i>".($_GET['sort'] == 'time' ? "most recent" : "mutual friends")."</i>)</h3>";
    
    // Main For Loop
    $topnumber = 10;
    echo "<ol>";
    foreach($cleanresults as $userdata){
      // Begin row
      echo "<li>";
      // Profile image
      echo "<img ".($userdata['uid']?"src=\"http://graph.facebook.com/".$userdata['uid']."/picture?width=24&height=24\"":"")." style=\"height:24;width:24;vertical-align: middle;\" />";
      // You have XX% similar friends to Facebook User
      echo "<span title=\"".preg_replace("~([^ ]*).*~i", "$1", ($userdata['name']?$userdata['name']:"Facebook User")).
        " has ".(!($userdata['friend_count'] || $userdata['mutual_friend_count'])?"?":number_format(($userdata['mutual_friend_count']/$userdata['friend_count']) * 100, 1)."%").
        " similar friends to you\">You have <b>".number_format($userdata['compatibility'], 1)."%</b> similar friends to <b>";
      // Link to profile
      echo "<a href=\"".($userdata['uid']?"http://facebook.com/profile.php?id=".$userdata['uid']."\" target=\"blank\"":"#\"");
      echo " title=\"".($userdata['name']?$userdata['name']:"Facebook User")."'s Profile\">";

      // Facebook User if unknown, else just name
      echo ($userdata['name']?$userdata['name']:"Facebook User");  
      echo "</a></b>";
      // Total friend count
      echo " (<i><b>".(!$userdata['mutual_friend_count']?"?":$userdata['mutual_friend_count'])."</b> of <b>".(!$userdata['friend_count']?"?":$userdata['friend_count'])."</b> friends</i>)</span></li>";
    }    
    echo "</ol>";
      
    // Show FQL Queries
    echo "<hr /><br /><b>FQL Queries (<a href=\"https://developers.facebook.com/docs/technical-guides/fql\" target=\"_blank\">info</a>):</b><br />";
    echo "Query friend list: ";
    echo "<a href=\"".htmlspecialchars($fql)."\" target=\"_blank\">SELECT uid1 FROM friend WHERE uid2 = user_id</a><br />";
    echo "Query friend data: ";
    echo "<a href=\"".htmlspecialchars($fqlquerypart)."\" target=\"_blank\">SELECT name, mutual_friend_count, sex, uid FROM user WHERE uid = user_id</a><br />";    
    
  } else {
    // No user, so print a link for the user to login      
    // We'll use the current URL as the redirect_uri, so we don't
    // need to specify it here.
    echo "<br /><div style=\"background-color:#D99898;;padding:10px;margin:2px;border:2px solid #960E0E;max-width:500px;text-align:center;height:auto;\">";
    $login_url = $facebook->getLoginUrl();
    if(isset($_GET['error'])){
      echo '<h2><font color="red">ERROR!</font> You didn\'t accept the permissions!<br />';
    } else {
      echo '<h2>It looks like you\'re not connected yet.<br />';
    }
    echo 'Please <a href="' . $login_url . '"><u>authenticate</u></a> to continue.</h2>';
    echo "</div>";
  } 
} catch(FacebookApiException $e) {
  // If the user is logged out, you can have a 
  // user ID even though the access token is invalid.
  // In this case, we'll get an exception, so we'll
  // just ask the user to login again here.
  echo "<br /><div style=\"background-color:#D99898;;padding:10px;margin:2px;border:2px solid #960E0E;max-width:500px;text-align:center;height:auto;\">";
  $login_url = $facebook->getLoginUrl();
  if(isset($_GET['error'])){
  echo '<h2><font color="red">ERROR!</font> You didn\'t accept the permissions!<br />';
  } else {
    echo '<h2>It looks like you\'re not connected yet.<br />';
  }
  echo 'Please <a href="' . $login_url . '"><u>login</u></a> to continue.</h2>';
  echo "</div><!-- ";
  error_log($e->getType());
  error_log($e->getMessage());
  echo " -->";
}   
?>
<br />
<hr />
<i>&copy; <?php echo date("Y"); ?> Zachary - Users: <?php echo $queryfriends[0]; ?>&nbsp;&nbsp;<div class="fb-share-button" data-href="http://zach.ie/fb/friends/" data-type="button_count"></div></i>
</body>
</html>
