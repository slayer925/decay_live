<!DOCTYPE html>
<html lang="en">
    <head>
        <?php include_once('head.php');?>
        <?php include_once("files/analytics.php"); ?>
    </head>
    <body>

<?php

    include_once('php-riot-api.php');
    //error_reporting(0); // This disables warnings and errors by php
    function clean($string) {
       $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

       return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
    }

    function showerror($msg,$err){
        echo '
        <br><br><br><br>
        <center>
            <div class="panel panel-default" style="width:400px;">
                <div class="panel-heading">
                    '.$msg.'
                </div>
                <div class="panel-body">
                    <p>'.$err.'</p>
                </div>

            </div>
            <a href="http://live.decayoflegends.com" class="btn btn-danger btn-3d">
                <i class="fa fa-angle-left"></i> Back
            </a>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="http://live.decayoflegends.com'.$_SERVER['REQUEST_URI'].'" class="btn btn-success btn-3d">
                <i class="fa fa-refresh"></i> Refresh
            </a>
        </center>';
        die();
    }

    $memcache = new Memcache;
    // TEST
    /*
    if ( $_GET['server']=="" ){
        $_GET['server']='NA';
        $_GET['summoner']='IRonPuPiL';
    }
    */
    
    /*
    http://www.jsoneditoronline.org/
    */

    $summoner_name = str_replace('_','',$_GET['summoner']);
    $region = strtolower($_GET['server']);

    switch ($region) {
        case 'na':
            $platform = 'NA1';
            $voice_server = 'NA';
            $spectator_url = 'spectator.na.lol.riotgames.com:80';
            break;

        case 'euw':
            $platform = 'EUW1';
            $voice_server = 'EU';
            $spectator_url = 'spectator.eu.lol.riotgames.com:80';
            break;

        case 'eune':
            $platform = 'EUN1';
            $voice_server = 'EU';
            $spectator_url = 'spectator.eu.lol.riotgames.com:8088';
            break;
        
        case 'br':
            $platform = 'BR1';
            $voice_server = 'LA';
            $spectator_url = 'spectator.br.lol.riotgames.com:80';
            break;
        
        case 'tr':
            $platform = 'TR1';
            $voice_server = 'EU';
            $spectator_url = 'spectator.tr.lol.riotgames.com:80';
            break;
        
        case 'oce':
            $platform = 'OC1';
            $voice_server = 'OCE';
            $spectator_url = 'spectator.oc1.lol.riotgames.com:80';
            break;
         
        case 'kr':
            $platform = 'KR';
            $voice_server = 'NA';
            $spectator_url = 'spectator.kr.lol.riotgames.com:80';
            break;

        case 'lan':
            $platform = 'LA1';
            $voice_server = 'LA';
            $spectator_url = 'spectator.br.lol.riotgames.com:80';
            break;

        case 'las':
            $platform = 'LA2';
            $voice_server = 'LA';
            $spectator_url = 'spectator.br.lol.riotgames.com:80';
            break;

        case 'ru':
            $platform = 'RU';
            $voice_server = 'EU';
            $spectator_url = 'spectator.tr.lol.riotgames.com:80';
            break;

        case 'pbe':
            $platform = 'PBE1';
            $voice_server = 'NA';
            $spectator_url = 'spectator.pbe1.lol.riotgames.com:8088';
            break;

        case 'jp':
            $platform = 'JP1';
            $voice_server = 'OCE';
            $spectator_url = 'spectator.jp1.lol.riotgames.com:80';
            break;

        default:
            $platform = 'EUW1';
            $voice_server = 'EU';
            $spectator_url = 'spectator.eu.lol.riotgames.com:80';
            //header('Location: index?err=Wrong%20server');
            //die();
            break;
    }
    //$platform = 'EUN1';
    //$platform = 'NA1';

    $test = new riotapi($region);

    // GET SUMMONER ID
    try{
        $r = $test->getSummonerByName($summoner_name);
    }catch (Exception $e) {
        //header('Location: /?err='.urlencode($e->getMessage()));
        showerror('There was a problem trying to get the summoner ID',$e->getMessage());
    }

    foreach ( $r as $summoner){
        $summoner_id = $summoner['id'];
    };
    //echo $summoner_id;

    // GET CURRENT GAME
    try{
        $p = $test->getCurrentGame($summoner_id,$platform);
    }catch (Exception $e) {
        //header('Location: /?err='.urlencode($e->getMessage()));
        showerror('There was a problem trying to get the current game',$e->getMessage());
    }
    $game_id = $p['gameId'];
    $game_mode = $p['gameMode'];
    if ( $game_mode == "CLASSIC" ){
        $game_mode = "NORMAL";
    }
    $mapID = $p['mapId'];
    switch ($mapID) {
        case '11':
            $map = "SR";
            break;
        default:
            $map = "";
            break;
    }
    //$game_type = $r['gameType'];
    //$game_start_time = $r['gameStartTime'];
    $game_length = $p['gameLength']; // seconds since started
    $game_key = $p['observers']['encryptionKey'];



    //LOAD CHAMPIONS
    $champs = $memcache->get('champs'.$region);
    $version = $memcache->get('version'.$region);
    if ( $champs === false || $version === false ) {
        $response = json_decode(file_get_contents('https://global.api.pvp.net/api/lol/static-data/'.$region.'/v1.2/champion?champData=all&api_key='.$_SERVER['API_KEY']),true);
        $champs = $response['keys'];
        $memcache->set('champs'.$region, $champs);
        $version = $response['version'];
        $memcache->set('version'.$region, $version);
    }

    //LOAD SUMMONER SPELLS
    $spells = $memcache->get('spells'.$region);
    $version_s = $memcache->get('version_s'.$region);
    if ( $spells === false || $version_s === false) {
        $response = json_decode(file_get_contents('https://global.api.pvp.net/api/lol/static-data/'.$region.'/v1.2/summoner-spell?locale=en_US&api_key='.$_SERVER['API_KEY']),true);
        foreach ($response['data'] as $spell_info) {
            $spells[$spell_info['id']] = $spell_info['key'];
        }
        $memcache->set('spells'.$region, $spells);
        $version_s = $response['version'];
        $memcache->set('version_s'.$region, $version_s);
    }

    //LOAD RUNES
    $runes = $memcache->get('runes'.$region);
    $version_r = $memcache->get('version_r'.$region);
    if ( $runes === false || $version_r === false ) {
        $response = json_decode(file_get_contents('https://global.api.pvp.net/api/lol/static-data/'.$region.'/v1.2/rune?locale=en_US&runeListData=all&api_key='.$_SERVER['API_KEY']),true);
        $runes = $response['data'];
        $memcache->set('runes'.$region, $runes);
        $version_r = $response['version'];
        $memcache->set('version_r'.$region, $version_r);
    }

    //LOAD MASTERIES
    $masteries = $memcache->get('masteries'.$region);
    $version_m = $memcache->get('version_m'.$region);
    if ( $masteries === false || $version_m === false ) {
        $response = json_decode(file_get_contents('https://global.api.pvp.net/api/lol/static-data/'.$region.'/v1.2/mastery?locale=en_US&masteryListData=all&api_key='.$_SERVER['API_KEY']),true);
        $masteries = $response['data'];
        $memcache->set('masteries'.$region, $masteries);
        $version_m = $response['version'];
        $memcache->set('version_m'.$region, $version_m);
    }

?>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/2.0.2/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/jquery.BA.ToolTip.js"></script>
<script src="js/jquery.easing.1.3.js"></script>
<script src="js/sweetalert.min.js"></script>


        <!-- Static navbar -->

        <section class="page">
            <div id="wrapper">
                <div class="content-wrapper container">

                    <div class="row">
                        <div class="col-xs-3" style="padding:20px 0px 0px 0px;">
                            <center>
                                <a href="http://live.decayoflegends.com" class="btn btn-danger btn-3d">
                                    <i class="fa fa-angle-left"></i> Back
                                </a>
                            </center>
                        </div>

                        <div class="col-xs-3" style="padding:0px;">
                            <center>
                                <a href="http://live.decayoflegends.com<?php echo $_SERVER['REQUEST_URI'];?>" target="_blank" title="Drag this to your bookmarks" data-toggle="tooltip" data-placement="bottom">
                                    <img src="files/favicon.png" class="fav">
                                </a>
                            </center>
                        </div>

                        <div class="col-xs-3" style="padding:0px;">
                            <center>
                                <a id="discord_link" target="_blank" style="cursor:pointer;" onclick="OpenInNewTab(document.getElementById('voice_url').value);" title="Join your teammates" data-toggle="tooltip" data-placement="bottom">
                                    <img src="images/Discord-Logo-Color.png" class="discord">
                                </a>
                            </center>
                        </div>
                        <div class="col-xs-3" style="padding:20px 0px 0px 0px;">
                            <center>
                                <a href="#" class="simple-alert btn btn-success btn-3d">
                                    Espectate <i class="fa fa-eye"></i>
                                </a>
                            </center>
                        </div>
                    </div>

                    <div class="row">


                        <?php
                            $league_names = Array();
                            $n_participants = 0;
                            $ownteam = false;
                            $hash = '';
                            foreach ( $p['participants'] as $participant){
                                if ( $participant['teamId']=='100'){
                                    $n_participants++;

                                    $hash = $hash . $participant['summonerId'];
                                    if ( $participant['summonerId'] == $summoner_id ){
                                        // This is the team
                                        $ownteam = true;
                                    }

                                    // MASTERY POINTS
                                    $mastery_points = '0';
                                    try{
                                        //echo 'https://'.$region.'.api.pvp.net/championmastery/location/'.$platform.'/player/'.$participant['summonerId'].'/champion/'.$participant['championId'].'?api_key='.$_SERVER['API_KEY'];
                                        $m = json_decode(file_get_contents('https://'.$region.'.api.pvp.net/championmastery/location/'.$platform.'/player/'.$participant['summonerId'].'/champion/'.$participant['championId'].'?api_key='.$_SERVER['API_KEY']),true);
                                        //$m = $test->getChampionMastery($platform,$participant['summonerId'],$participant['championId']);
                                        $mastery_points = $m['championPoints'];

                                            // call example:
                                            // https://tr.api.pvp.net/championmastery/location/TR1/player/4502382/champion/67?api_key=
                                    }catch (Exception $e) {
                                        //echo $e->getMessage(); // returns 415 wtf
                                    }
                                    

                                    // GET LEVEL
                                    $summoner_level='??';
                                    try{
                                        $response = $test->getSummoner($participant['summonerId']);
                                        $summoner_level = $response[$participant['summonerId']]['summonerLevel'];
                                    }catch (Exception $e) {
                                        //echo $e->getMessage(); // can return 404 not found if not ranked games played?
                                    }

                                    // RANKED STATS
                                    $winrate = '--';
                                    $ngames = '';
                                    $kda = '--';
                                    $killspergame = '-';
                                    $deathspergame = '-';
                                    $assistspergame = '-';
                                    try{
                                        $s = $test->getStats($participant['summonerId'],'ranked');
                                        foreach ($s['champions'] as $championstats) {
                                            if ( $championstats['id'] == $participant['championId'] ){
                                                if ( $championstats['stats']['totalSessionsPlayed']!=0 ){
                                                    $winrate = number_format($championstats['stats']['totalSessionsWon']/$championstats['stats']['totalSessionsPlayed'],2)*100;
                                                    $ngames = $championstats['stats']['totalSessionsPlayed'];
                                                    $kda = '<i class="fa fa-star"></i>';
                                                    $killspergame = number_format($championstats['stats']['totalChampionKills']/$championstats['stats']['totalSessionsPlayed'],1);
                                                    $deathspergame = number_format($championstats['stats']['totalDeathsPerSession']/$championstats['stats']['totalSessionsPlayed'],1);
                                                    $assistspergame = number_format($championstats['stats']['totalAssists']/$championstats['stats']['totalSessionsPlayed'],1);
                                                    if ( $championstats['stats']['totalDeathsPerSession']!=0 ){
                                                        $kda = number_format(($championstats['stats']['totalChampionKills']+$championstats['stats']['totalAssists'])/$championstats['stats']['totalDeathsPerSession'],2);
                                                    }
                                                }
                                            }
                                        }
                                    }catch (Exception $e) {
                                        //echo $e->getMessage(); // can return 404 not found if not ranked games played?
                                    }

                                    $rankedwins = '-';
                                    $rankedlosses = '-';
                                    $normalwins = '-';
                                    
                                    try{
                                        $r = $test->getStats($participant['summonerId'],'summary');
                                        foreach ($r['playerStatSummaries'] as $queue) {
                                            if ( $queue['playerStatSummaryType'] == 'RankedSolo5x5' ){
                                                $rankedwins = $queue['wins'];
                                                $rankedlosses = $queue['losses'];
                                            }elseif ( $queue['playerStatSummaryType'] == 'Unranked' ){
                                                $normalwins = $queue['wins'];
                                            }
                                        }
                                    }catch (Exception $e) {
                                        //echo $e->getMessage(); // can return 404 not found if not ranked games played?
                                    }

                                    $tier = 'UNRANKED';
                                    $division = '';
                                    $league_name = '';
                                    $league_points = 'x';
                                    $ishotstreak = false;
                                    $isveteran = false;
                                    $isfreshblood = false;
                                    $series = '';
                                    try{
                                        $l = $test->getLeague($participant['summonerId'],'entry');
                                        foreach ($l[$participant['summonerId']] as $league) {
                                            if ( $league['queue'] == 'RANKED_SOLO_5x5' ){
                                                $tier = $league['tier'];
                                                foreach ($league['entries'] as $teams) {
                                                    if ( $teams['playerOrTeamId'] == $participant['summonerId'] ){
                                                        $division = $teams['division'];
                                                        $league_points = $teams['leaguePoints'];
                                                        if ( $league_points == '100' ){
                                                            $series = $teams['miniSeries']['progress'];
                                                        }
                                                        $ishotstreak = $teams['isHotStreak'];
                                                        $isveteran = $teams['isVeteran'];
                                                        $isfreshblood = $teams['isFreshBlood'];
                                                    }
                                                }
                                                if ( $tier != 'CHALLENGER' && $tier != 'MASTER' ){
                                                    //$league_name = $league['name'];
                                                    array_push($league_names, $league['name']);
                                                }
                                            }
                                        }
                                    }catch (Exception $e) {
                                        //echo $e->getMessage(); // can return 404 not found if not ranked games played?
                                    }
                                    if ( $tier == 'CHALLENGER' || $tier == 'MASTER' ){
                                        $division = '';
                                    }
                        ?>

                        <div class="col-md-5ths">
                            <div class="tile">
                                <div class="tile-title clearfix">
                                    <?php echo $participant['summonerName']; ?>
                                    <?php echo ' <span style="display:none">'.$participant['summonerId'].'</span>';?>
                                    <span class="pull-right" style="font-size:10px;" title="summoner level">(<?php echo $summoner_level;?>)</span>
                                </div><!--.tile-title-->
                                
                                <div class="tile-body clearfix">
                                    <div style="float:left;height:48px; width:48px;"><img src="http://ddragon.leagueoflegends.com/cdn/<?php echo $version;?>/img/champion/<?php echo $champs[$participant['championId']];?>.png" width="48" title="<?php echo $champs[$participant['championId']]; //echo $participant['championId'];?>"></div>
                                    
                                    <div class="summoners">
                                        <span>
                                            <img class="summoner1" src="http://ddragon.leagueoflegends.com/cdn/<?php echo $version_s;?>/img/spell/<?php echo $spells[$participant['spell1Id']];?>.png">
                                        </span>
                                        <span>
                                            <img class="summoner2" src="http://ddragon.leagueoflegends.com/cdn/<?php echo $version_s;?>/img/spell/<?php echo $spells[$participant['spell2Id']];?>.png">
                                        </span>
                                    </div>
                                    <h4 class="pull-right"><?php echo $kda;?> KDA</h4><br>
                                    <h5 class="pull-right"><?php echo ($killspergame!='-')?$killspergame.' / '.$deathspergame.' / '.$assistspergame:'<small>NO RANKED GAMES</small>';?></h5>
                                </div><!--.tile-body-->
                                <div class="tile-extra">
                                    <span class="info-left">
                                        <span class="wr">WIN RATE<?php echo ($ngames!='')?' ('.$ngames.')':'';?></span>
                                        <?php echo $winrate . '%';?>
                                    </span>
                                    <span class="premade <?php echo clean($league_name);?>" style="display:none;" title="possible premade">
                                        <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" class="warrior">
                                          <path d="M50,6.1L50,6.1c-15.5,0-28.1,11.2-28.1,25v51.4l17,11.4V48.2l-5.3-1.3L31,39.8l14.7,3.5v18.9l4,3.1l4.3-3.1V43.4l15.4-3.6  l-2.7,7.1l-6,1.4v45.7l17.5-11.4V31.1C78.1,17.3,65.5,6.1,50,6.1z"/>
                                        </svg>
                                    </span>
                                    <span class="info-right">
                                        <span class="mp">MASTERY POINTS</span>
                                        <?php 
                                            if ( $mastery_points > 999999 ){
                                                echo number_format($mastery_points/1000000,2).'M';
                                            }elseif ($mastery_points > 999){
                                                echo number_format($mastery_points/1000,2).'K';
                                            }elseif ($mastery_points == ''){
                                                echo '0';
                                            }else{
                                                echo $mastery_points;
                                            }
                                        ?>
                                    </span>
                                </div><!--.tile footer-->
                                <div class="tile-band <?php echo ($participant['summonerId']==$summoner_id?'me':'blueteam');?>">
                                    <img src="images/<?php echo strtolower($tier);?>.png" width="40">
                                    <span class="division"><?php echo $tier.' '.$division;?></span>
                                    <span class="lp"><?php echo ($tier!='UNRANKED')?intval($league_points).' LP':'';?></span>
                                    <span class="series">
                                        <?php
                                            if ( $league_points=='100' ){
                                                $chars = str_split($series);
                                                foreach($chars as $char){
                                                    switch ($char) {
                                                        case 'N':
                                                            echo '<i class="fa fa-circle-o"></i>';
                                                            break;
                                                        case 'W':
                                                            echo '<i class="fa fa-circle green" title="WIN"></i>';
                                                            break;
                                                        case 'L':
                                                            //echo '<i class="fa fa-ban red" title="LOSS"></i>';
                                                            echo '<i class="fa fa-circle red" title="LOSS"></i>';
                                                            break;
                                                        default:
                                                            # code...
                                                            break;
                                                    }
                                                }
                                                //echo $series;
                                            }else{
                                                if ($ishotstreak){
                                                    echo '<i class="fa fa-rocket red" title="HotStreak: 3+ wins in a row"></i>';
                                                }elseif ($isveteran){
                                                    echo '<i class="fa fa-shield purple" title="Veteran: 100+ games in the same league"></i>';
                                                }elseif ($isfreshblood){
                                                    echo '<i class="fa fa-asterisk blue" title="Recruit: Recently joined"></i>';
                                                }

                                            }
                                        ?>
                                    </span>
                                </div><!--.tile band-->
                                <div class="tile-footer2">
                                    <span class="info-left">
                                        <span class="nw">NORMAL WINS</span>
                                        <?php echo $normalwins; ?>
                                    </span>
                                    <span class="info-right">
                                        <span class="rw">RANKED WINS / LOSSES</span>
                                        <?php echo $rankedwins . ' / ' . $rankedlosses; ?>
                                    </span>
                                </div><!--.tile footer-->
                                <div class="tile-footer3">
                                    <span class="info-left">
                                        <span class="nw">RUNES</span>

                                        <span class="tip">
                                            <?php
                                                $quintessence = [];
                                                foreach ($participant['runes'] as $runes_info) {
                                                    echo $runes_info['count'].' x ('.$runes[$runes_info['runeId']]['description'].')<br>';
                                                    if ( $runes[$runes_info['runeId']]['rune']['type']=='black' ){
                                                        array_push($quintessence, $runes_info);
                                                    }
                                                }
                                            ?>
                                        </span>
                                        <?php
                                            foreach ($quintessence as $quintessence_info) {
                                                for ($i=0; $i < $quintessence_info['count']; $i++) { 
                                                    echo '<img src="http://ddragon.leagueoflegends.com/cdn/'.$version_r.'/img/rune/'.$runes[$quintessence_info['runeId']]['image']['full'].'" width="16">';
                                                }
                                            }
                                        ?>

                                    </span>
                                    <span>
                                        <?php
                                            $keystones_array = ['6161','6162','6164','6261','6262','6263','6361','6362','6363'];
                                            $ferocity = 0;
                                            $cunning = 0;
                                            $resolve = 0;
                                            $keystone = '';
                                            foreach ($participant['masteries'] as $mastery_info) {
                                                switch ($masteries[$mastery_info['masteryId']]['masteryTree']) {
                                                    case 'Ferocity':
                                                        $ferocity = $ferocity + $mastery_info['rank'];
                                                        break;
                                                    case 'Cunning':
                                                        $cunning = $cunning + $mastery_info['rank'];
                                                        break;
                                                    case 'Resolve':
                                                        $resolve = $resolve + $mastery_info['rank'];
                                                        break;
                                                    default:
                                                        break;
                                                }
                                                if ( in_array($mastery_info['masteryId'], $keystones_array) ){
                                                    $keystone = $mastery_info['masteryId'];
                                                }
                                            }
                                            if ( $keystone!='' ){

                                        ?>
	                                        <img class="circular" src="http://ddragon.leagueoflegends.com/cdn/<?php echo $version_m;?>/img/mastery/<?php echo $keystone;?>.png" width="23" data-html="true" title="<?php echo $masteries[$keystone]['name'].'<br><br>'.$masteries[$keystone]['description'][0];?>" data-toggle="tooltip" data-placement="bottom">
                                    	<?php
                                    		}
                                    	?>
                                    </span>
                                    <span class="info-right">
                                        <span class="rw">MASTERIES</span>
                                        <span class="label masteries" style="background-color:#CA2F00;" title="Ferocity"><?php echo $ferocity;?></span>
                                        <span class="label masteries" style="background-color:#0C63C4;" title="Cunning"><?php echo $cunning;?></span>
                                        <span class="label masteries" style="background-color:#489006;" title="Resolve"><?php echo $resolve;?></span>
                                    </span>
                                </div><!--.tile footer-->

                            </div><!-- .tile-->
                        </div><!--end .col-->

                        <?php

                                }//team100
                            };//foreach

                            //search for premades
                            $array_unique = array_unique($league_names);
                            $array_diff = array_diff_assoc($league_names, $array_unique);
                            //$array_diff=$array_unique; // to show every icon
                            echo '<script>
                                $(document).ready(function() {';
                            $color = ['#8e44ad','#d35400','#2980b9'];

                            $i = 0;
                            foreach ($array_diff as $league) {
                                echo '$( ".'.clean($league).'" ).css("fill","'.$color[$i].'");';
                                echo '$( ".'.clean($league).'" ).css("display","block");';
                                $i++;
                            }
                            /*
                            if ( $ownteam ){
                                echo '$("#discord_link").attr("href", "http://live.decayoflegends.com/voice?server='.$voice_server.'&hash='.$hash.'");';
                            }
                            */
                            echo '}
                            </script>';
                            if ( $ownteam ){
                                echo '<input type="text" id="voice_url" style="display:none" value="http://live.decayoflegends.com/voice?server='.$voice_server.'&hash='.$hash.'">';
                            }
                        ?>

                    
                    </div>

                    <div class="row" style="min-height:60px;">
                        <div class="col-xs-4" style="padding-top:5px;">
                            <center>
                                <!--
                                <img src="files/logo.png" height="50">
                                -->
                            </center>
                        </div>

                        <div class="col-xs-4">
                            <center>
                                <!--<span style="float:left;font-size:20px;padding-top:18px;"><i><?php echo $game_mode.' '.$n_participants.'x'.$n_participants;?></i></span>-->
                                <span style="float:left;font-size:20px;padding-top:18px;"><i><?php echo $map.' '.$n_participants.'x'.$n_participants;?></i></span>
                                <span style="float:right;font-size:20px;padding-top:18px;"><i class="fa fa-circle fa-blink red"></i> <span id="timer"><?php echo str_pad(floor($game_length/60), 2, "0", STR_PAD_LEFT).':'.str_pad($game_length%60, 2, "0", STR_PAD_LEFT);?></span></span>
                                <img src="images/vs.png" style="width:50px;padding-top:5px;">
                            </center>
                        </div>

                        <div class="col-xs-4" style="height:50px !important;padding-top:5px;">
                            <center>
                            <!--
                            <div class="alert alert-info text-center" role="alert" style="height:50px;margin:0px !important;padding:0px;border-color: #FFF;background-color: #ffffff;background-image: url('files/adblock.png');background-repeat: no-repeat;background-position: center;">
                            

                                <script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
                                <ins class="adsbygoogle"
                                     style="display:inline-block;width:320px;height:50px"
                                     data-ad-client="ca-pub-5490252948846555"
                                     data-ad-slot="2817275664"></ins>
                                <script>
                                (adsbygoogle = window.adsbygoogle || []).push({});
                                </script>
                            </div>
                            !-->
                            </center>
                        </div>

                    </div>

                    <div class="row">


                        <?php
                            $league_names = Array();
                            $ownteam = false;
                            $hash = '';
                            foreach ( $p['participants'] as $participant){
                                if ( $participant['teamId']=='200'){

                                    $hash = $hash . $participant['summonerId'];
                                    if ( $participant['summonerId'] == $summoner_id ){
                                        // This is the team
                                        $ownteam = true;
                                    }

                                    // MASTERY POINTS
                                    $mastery_points = '0';
                                    try{
                                        //echo 'https://'.$region.'.api.pvp.net/championmastery/location/'.$platform.'/player/'.$participant['summonerId'].'/champion/'.$participant['championId'].'?api_key='.$_SERVER['API_KEY'];
                                        $m = json_decode(file_get_contents('https://'.$region.'.api.pvp.net/championmastery/location/'.$platform.'/player/'.$participant['summonerId'].'/champion/'.$participant['championId'].'?api_key='.$_SERVER['API_KEY']),true);
                                        //$m = $test->getChampionMastery($platform,$participant['summonerId'],$participant['championId']);
                                        $mastery_points = $m['championPoints'];

                                            // call example:
                                            // https://tr.api.pvp.net/championmastery/location/TR1/player/4502382/champion/67?api_key=
                                    }catch (Exception $e) {
                                        //echo $e->getMessage(); // returns 415?
                                    }
                                    

                                    // GET LEVEL
                                    $summoner_level='??';
                                    try{
                                        $response = $test->getSummoner($participant['summonerId']);
                                        $summoner_level = $response[$participant['summonerId']]['summonerLevel'];
                                    }catch (Exception $e) {
                                        //echo $e->getMessage(); // can return 404 not found if not ranked games played?
                                    }

                                    // RANKED STATS
                                    $winrate = '--';
                                    $ngames = '';
                                    $kda = '--';
                                    $killspergame = '-';
                                    $deathspergame = '-';
                                    $assistspergame = '-';
                                    try{
                                        $s = $test->getStats($participant['summonerId'],'ranked');
                                        foreach ($s['champions'] as $championstats) {
                                            if ( $championstats['id'] == $participant['championId'] ){
                                                if ( $championstats['stats']['totalSessionsPlayed']!=0 ){
                                                    $winrate = number_format($championstats['stats']['totalSessionsWon']/$championstats['stats']['totalSessionsPlayed'],2)*100;
                                                    $ngames = $championstats['stats']['totalSessionsPlayed'];
                                                    $kda = '<i class="fa fa-star"></i>';
                                                    $killspergame = number_format($championstats['stats']['totalChampionKills']/$championstats['stats']['totalSessionsPlayed'],1);
                                                    $deathspergame = number_format($championstats['stats']['totalDeathsPerSession']/$championstats['stats']['totalSessionsPlayed'],1);
                                                    $assistspergame = number_format($championstats['stats']['totalAssists']/$championstats['stats']['totalSessionsPlayed'],1);
                                                    if ( $championstats['stats']['totalDeathsPerSession']!=0 ){
                                                        $kda = number_format(($championstats['stats']['totalChampionKills']+$championstats['stats']['totalAssists'])/$championstats['stats']['totalDeathsPerSession'],2);
                                                    }
                                                }
                                            }
                                        }
                                    }catch (Exception $e) {
                                        //echo $e->getMessage(); // can return 404 not found if not ranked games played?
                                    }

                                    $rankedwins = '-';
                                    $rankedlosses = '-';
                                    $normalwins = '-';
                                    try{
                                        $r = $test->getStats($participant['summonerId'],'summary');
                                        foreach ($r['playerStatSummaries'] as $queue) {
                                            if ( $queue['playerStatSummaryType'] == 'RankedSolo5x5' ){
                                                $rankedwins = $queue['wins'];
                                                $rankedlosses = $queue['losses'];
                                            }elseif ( $queue['playerStatSummaryType'] == 'Unranked' ){
                                                $normalwins = $queue['wins'];
                                            }
                                        }


                                    }catch (Exception $e) {
                                        //echo $e->getMessage(); // can return 404 not found if not ranked games played?
                                    }

                                    $tier = 'UNRANKED';
                                    $division = '';
                                    $league_name = '';
                                    $league_points = 'x';
                                    $ishotstreak = false;
                                    $isveteran = false;
                                    $isfreshblood = false;
                                    $series = '';
                                    try{
                                        $l = $test->getLeague($participant['summonerId'],'entry');
                                        foreach ($l[$participant['summonerId']] as $league) {
                                            if ( $league['queue'] == 'RANKED_SOLO_5x5' ){
                                                $tier = $league['tier'];
                                                foreach ($league['entries'] as $teams) {
                                                    if ( $teams['playerOrTeamId'] == $participant['summonerId'] ){
                                                        $division = $teams['division'];
                                                        $league_points = $teams['leaguePoints'];
                                                        if ( $league_points == '100' ){
                                                            $series = $teams['miniSeries']['progress'];
                                                        }
                                                        $ishotstreak = $teams['isHotStreak'];
                                                        $isveteran = $teams['isVeteran'];
                                                        $isfreshblood = $teams['isFreshBlood'];
                                                    }
                                                }
                                                if ( $tier != 'CHALLENGER' && $tier != 'MASTER' ){
                                                    //$league_name = $league['name'];
                                                    array_push($league_names, $league['name']);
                                                }
                                            }
                                        }
                                    }catch (Exception $e) {
                                        //echo $e->getMessage(); // can return 404 not found if not ranked games played?
                                    }
                                    if ( $tier == 'CHALLENGER' || $tier == 'MASTER' ){
                                        $division = '';
                                    }
                        ?>

                        <div class="col-md-5ths">
                            <div class="tile">
                                <div class="tile-title clearfix">
                                    <?php echo $participant['summonerName']; ?>
                                    <?php echo ' <span style="display:none">'.$participant['summonerId'].'</span>';?>
                                    <span class="pull-right" style="font-size:10px;" title="Summoner level">(<?php echo $summoner_level;?>)</span>
                                </div><!--.tile-title-->
                                <div class="tile-body clearfix">
                                    <div style="float:left;height:48px; width:48px;"><img src="http://ddragon.leagueoflegends.com/cdn/<?php echo $version;?>/img/champion/<?php echo $champs[$participant['championId']];?>.png" width="48" title="<?php echo $champs[$participant['championId']]; //echo $participant['championId'];?>"></div>
                                    
                                    <div class="summoners">
                                        <span>
                                            <img class="summoner1" src="http://ddragon.leagueoflegends.com/cdn/<?php echo $version_s;?>/img/spell/<?php echo $spells[$participant['spell1Id']];?>.png">
                                        </span>
                                        <span>
                                            <img class="summoner2" src="http://ddragon.leagueoflegends.com/cdn/<?php echo $version_s;?>/img/spell/<?php echo $spells[$participant['spell2Id']];?>.png">
                                        </span>
                                    </div>
                                    <h4 class="pull-right"><?php echo $kda;?> KDA</h4><br>
                                    <h5 class="pull-right"><?php echo ($killspergame!='-')?$killspergame.' / '.$deathspergame.' / '.$assistspergame:'<small>NO RANKED GAMES</small>';?></h5>
                                </div><!--.tile-body-->
                                <div class="tile-extra">
                                    <span class="info-left">
                                        <span class="wr">WIN RATE<?php echo ($ngames!='')?' ('.$ngames.')':'';?></span>
                                        <?php echo $winrate . '%';?>
                                    </span>
                                    <span class="premade <?php echo clean($league_name);?>" style="display:none;" title="possible premade">
                                        <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" class="warrior">
                                          <path d="M50,6.1L50,6.1c-15.5,0-28.1,11.2-28.1,25v51.4l17,11.4V48.2l-5.3-1.3L31,39.8l14.7,3.5v18.9l4,3.1l4.3-3.1V43.4l15.4-3.6  l-2.7,7.1l-6,1.4v45.7l17.5-11.4V31.1C78.1,17.3,65.5,6.1,50,6.1z"/>
                                        </svg>
                                    </span>
                                    <span class="info-right">
                                        <span class="mp">MASTERY POINTS</span>
                                        <?php 
                                            if ( $mastery_points > 999999 ){
                                                echo number_format($mastery_points/1000000,2).'M';
                                            }elseif ($mastery_points > 999){
                                                echo number_format($mastery_points/1000,2).'K';
                                            }elseif ($mastery_points == ''){
                                                echo '0';
                                            }else{
                                                echo $mastery_points;
                                            }
                                        ?>
                                    </span>
                                </div><!--.tile footer-->
                                <div class="tile-band <?php echo ($participant['summonerId']==$summoner_id?'me':'redteam');?>">
                                    <img src="images/<?php echo strtolower($tier);?>.png" width="40">
                                    <span class="division"><?php echo $tier.' '.$division;?></span>
                                    <span class="lp"><?php echo ($tier!='UNRANKED')?intval($league_points).' LP':'';?></span>
                                    <span class="series">
                                        <?php
                                            if ( $league_points=='100' ){
                                                $chars = str_split($series);
                                                foreach($chars as $char){
                                                    switch ($char) {
                                                        case 'N':
                                                            echo '<i class="fa fa-circle-o"></i>';
                                                            break;
                                                        case 'W':
                                                            echo '<i class="fa fa-circle green" title="WIN"></i>';
                                                            break;
                                                        case 'L':
                                                            //echo '<i class="fa fa-ban red" title="LOSS"></i>';
                                                            echo '<i class="fa fa-circle red" title="LOSS"></i>';
                                                            break;
                                                        default:
                                                            # code...
                                                            break;
                                                    }
                                                }
                                                //echo $series;
                                            }else{
                                                if ($ishotstreak){
                                                    echo '<i class="fa fa-rocket red" title="HotStreak: 3+ wins in a row"></i>';
                                                }elseif ($isveteran){
                                                    echo '<i class="fa fa-shield purple" title="Veteran: 100+ games in the same league"></i>';
                                                }elseif ($isfreshblood){
                                                    echo '<i class="fa fa-asterisk blue" title="Recruit: Recently joined"></i>';
                                                }

                                            }
                                        ?>
                                    </span>
                                </div><!--.tile band-->
                                <div class="tile-footer2">
                                    <span class="info-left">
                                        <span class="nw">NORMAL WINS</span>
                                        <?php echo $normalwins; ?>
                                    </span>
                                    <span class="info-right">
                                        <span class="rw">RANKED WINS / LOSSES</span>
                                        <?php echo $rankedwins . ' / ' . $rankedlosses; ?>
                                    </span>
                                </div><!--.tile footer-->
                                <div class="tile-footer3">
                                    <span class="info-left">
                                        <span class="nw">RUNES</span>

                                        <span class="tip">
                                            <?php
                                                $quintessence = [];
                                                foreach ($participant['runes'] as $runes_info) {
                                                    echo $runes_info['count'].' x ('.$runes[$runes_info['runeId']]['description'].')<br>';
                                                    if ( $runes[$runes_info['runeId']]['rune']['type']=='black' ){
                                                        array_push($quintessence, $runes_info);
                                                    }
                                                }
                                            ?>
                                        </span>
                                        <?php
                                            foreach ($quintessence as $quintessence_info) {
                                                for ($i=0; $i < $quintessence_info['count']; $i++) { 
                                                    echo '<img src="http://ddragon.leagueoflegends.com/cdn/'.$version_r.'/img/rune/'.$runes[$quintessence_info['runeId']]['image']['full'].'" width="16">';
                                                }
                                            }
                                        ?>

                                    </span>
                                    <span>
                                        <?php
                                            $keystones_array = ['6161','6162','6164','6261','6262','6263','6361','6362','6363'];
                                            $ferocity = 0;
                                            $cunning = 0;
                                            $resolve = 0;
                                            $keystone = '';
                                            foreach ($participant['masteries'] as $mastery_info) {
                                                switch ($masteries[$mastery_info['masteryId']]['masteryTree']) {
                                                    case 'Ferocity':
                                                        $ferocity = $ferocity + $mastery_info['rank'];
                                                        break;
                                                    case 'Cunning':
                                                        $cunning = $cunning + $mastery_info['rank'];
                                                        break;
                                                    case 'Resolve':
                                                        $resolve = $resolve + $mastery_info['rank'];
                                                        break;
                                                    default:
                                                        break;
                                                }
                                                if ( in_array($mastery_info['masteryId'], $keystones_array) ){
                                                    $keystone = $mastery_info['masteryId'];
                                                }
                                            }
											if ( $keystone!='' ){

                                        ?>
	                                        <img class="circular" src="http://ddragon.leagueoflegends.com/cdn/<?php echo $version_m;?>/img/mastery/<?php echo $keystone;?>.png" width="23" data-html="true" title="<?php echo $masteries[$keystone]['name'].'<br><br>'.$masteries[$keystone]['description'][0];?>" data-toggle="tooltip" data-placement="top">
                                    	<?php
                                    		}
                                    	?>
                                    </span>
                                    <span class="info-right">
                                        <span class="rw">MASTERIES</span>
                                        <span class="label masteries" style="background-color:#CA2F00;" title="Ferocity"><?php echo $ferocity;?></span>
                                        <span class="label masteries" style="background-color:#0C63C4;" title="Cunning"><?php echo $cunning;?></span>
                                        <span class="label masteries" style="background-color:#489006;" title="Resolve"><?php echo $resolve;?></span>
                                    </span>
                                </div><!--.tile footer-->

                            </div><!-- .tile-->
                        </div><!--end .col-->

                        <?php

                                }//team100
                            };//foreach

                            //search for premades
                            $array_unique = array_unique($league_names);
                            $array_diff = array_diff_assoc($league_names, $array_unique);
                            //$array_diff=$array_unique; // to show every icon
                            echo '<script>
                                window.onload = function () {';
                            $color = ['#8e44ad','#d35400','#2980b9'];

                            $i = 0;
                            foreach ($array_diff as $league) {
                                echo '$( ".'.clean($league).'" ).css("fill","'.$color[$i].'");';
                                echo '$( ".'.clean($league).'" ).css("display","block");';
                                $i++;
                            }
                            /*
                            if ( $ownteam ){
                                echo '$("#discord_link").attr("href", "http://live.decayoflegends.com/voice?server='.$voice_server.'&hash='.$hash.'");';
                            }
                            */
                            echo '}
                            </script>';
                            if ( $ownteam ){
                                echo '<input type="text" id="voice_url" style="display:none" value="http://live.decayoflegends.com/voice?server='.$voice_server.'&hash='.$hash.'">';
                            }
                        ?>

                    
                    </div>


<div class="row" style="height:60px;">
    <div class="col-xs-6" style="padding-top:25px;">
        <center>
            <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
            <input type="hidden" name="cmd" value="_s-xclick">
            <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----MIIHLwYJKoZIhvcNAQcEoIIHIDCCBxwCAQExggEwMIIBLAIBADCBlDCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20CAQAwDQYJKoZIhvcNAQEBBQAEgYCLiBXp5BAppmE3DWs3XiMkWT+YnoftpoebSbJ7A2t2gKROR3nicv66DBOMYMAH59iqW9A3VAehq6Cwo8nfUZIe8E5yXB/KVNUhGnOe2wuzVdOdNmmvxnvlZLQ9QpN5GEhMikIRIj7mxmeflp3UsspaOgW9QSwhlu9G0sYHbrpGbDELMAkGBSsOAwIaBQAwgawGCSqGSIb3DQEHATAUBggqhkiG9w0DBwQIAH1mUGufNwGAgYjR58oMR15BCvZhLGhHxGFVUGWvvdvQDMfSJMzdGpI6gcJBUnppvhpdy41sMxmM8jPWZTQdHSXslDP+tqMkwKN/v71TVK/qybW6PwLhSeF3JvCxcqFxWbz8eRK6gie4Z1tTZ+Cvd9V2f9B5jmDO/WbYl6jIpjC/Lqhl3A8duAeA6yo5ufR4me6/oIIDhzCCA4MwggLsoAMCAQICAQAwDQYJKoZIhvcNAQEFBQAwgY4xCzAJBgNVBAYTAlVTMQswCQYDVQQIEwJDQTEWMBQGA1UEBxMNTW91bnRhaW4gVmlldzEUMBIGA1UEChMLUGF5UGFsIEluYy4xEzARBgNVBAsUCmxpdmVfY2VydHMxETAPBgNVBAMUCGxpdmVfYXBpMRwwGgYJKoZIhvcNAQkBFg1yZUBwYXlwYWwuY29tMB4XDTA0MDIxMzEwMTMxNVoXDTM1MDIxMzEwMTMxNVowgY4xCzAJBgNVBAYTAlVTMQswCQYDVQQIEwJDQTEWMBQGA1UEBxMNTW91bnRhaW4gVmlldzEUMBIGA1UEChMLUGF5UGFsIEluYy4xEzARBgNVBAsUCmxpdmVfY2VydHMxETAPBgNVBAMUCGxpdmVfYXBpMRwwGgYJKoZIhvcNAQkBFg1yZUBwYXlwYWwuY29tMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDBR07d/ETMS1ycjtkpkvjXZe9k+6CieLuLsPumsJ7QC1odNz3sJiCbs2wC0nLE0uLGaEtXynIgRqIddYCHx88pb5HTXv4SZeuv0Rqq4+axW9PLAAATU8w04qqjaSXgbGLP3NmohqM6bV9kZZwZLR/klDaQGo1u9uDb9lr4Yn+rBQIDAQABo4HuMIHrMB0GA1UdDgQWBBSWn3y7xm8XvVk/UtcKG+wQ1mSUazCBuwYDVR0jBIGzMIGwgBSWn3y7xm8XvVk/UtcKG+wQ1mSUa6GBlKSBkTCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb22CAQAwDAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQUFAAOBgQCBXzpWmoBa5e9fo6ujionW1hUhPkOBakTr3YCDjbYfvJEiv/2P+IobhOGJr85+XHhN0v4gUkEDI8r2/rNk1m0GA8HKddvTjyGw/XqXa+LSTlDYkqI8OwR8GEYj4efEtcRpRYBxV8KxAW93YDWzFGvruKnnLbDAF6VR5w/cCMn5hzGCAZowggGWAgEBMIGUMIGOMQswCQYDVQQGEwJVUzELMAkGA1UECBMCQ0ExFjAUBgNVBAcTDU1vdW50YWluIFZpZXcxFDASBgNVBAoTC1BheVBhbCBJbmMuMRMwEQYDVQQLFApsaXZlX2NlcnRzMREwDwYDVQQDFAhsaXZlX2FwaTEcMBoGCSqGSIb3DQEJARYNcmVAcGF5cGFsLmNvbQIBADAJBgUrDgMCGgUAoF0wGAYJKoZIhvcNAQkDMQsGCSqGSIb3DQEHATAcBgkqhkiG9w0BCQUxDxcNMTQwODAzMDk1MDU4WjAjBgkqhkiG9w0BCQQxFgQUYSMXFgSswxB6VakMO4i/p+Z6ONgwDQYJKoZIhvcNAQEBBQAEgYCtdK6KdnQcnRhYvdrAw+1bLHV9b5tSt6Uh9lzC1z0L0NdACec/2myOnL9uq25hsoI/VqifaRKrkVZ/PH7IdRQRyWOOlNr3yJnx8OaCMSs2sCQ/ihMbJ4hfIu2G37fMrRWpoJMx+/R/XiuFykvkLtdTef24c+ehTbg3cVD6SZLe1g==-----END PKCS7-----
            ">
            <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
            <img alt="" border="0" src="https://www.paypalobjects.com/es_ES/i/scr/pixel.gif" width="1" height="1">
        </form>
        </center>
    </div>

    <div class="col-xs-6" style="padding-top:10px;">
        <center>
            Bugs? Any questions?<br><small>support@decayoflegends.com</small>
        </center>
    </div>

</div>

            </div>
        </section>



        
        <script>
        function OpenInNewTab(url) {
          var win = window.open(url, '_blank');
          win.focus();
        }

        $(document).ready(function (e) {
            jQuery('.tip').parent().BAToolTip({ tipOpacity: 0.85, tipOffset: -10 });
            $('[data-toggle="tooltip"]').tooltip();
            var $worked = $("#timer");
            function update() {
                var myTime = $worked.html();
                var ss = myTime.split(":");
                var dt = new Date();
                dt.setHours(0);
                dt.setMinutes(ss[0]);
                dt.setSeconds(ss[1]);
                
                var dt2 = new Date(dt.valueOf() + 1000);
                var temp = dt2.toTimeString().split(" ");
                var ts = temp[0].split(":");
                
                $worked.html(ts[1]+":"+ts[2]);
                setTimeout(update, 1000);
            }
            setTimeout(update, 1000);
        });
        document.querySelector('.simple-alert').onclick = function () {
            swal({
                title: "Press Windows key + R and paste the following code:",
                //text: '"C:\\Riot Games\\League of Legends\\RADS\\solutions\\lol_game_client_sln\\releases\\0.0.1.119\\deploy\\League of Legends.exe" "8394" "LoLLauncher.exe" "" "spectator spectator.<?php echo strtolower($platform);?>.lol.riotgames.com:80 <?php echo $game_key . " " . $game_id . " " . $platform;?>\"'
                text: '"C:\\Riot Games\\League of Legends\\RADS\\solutions\\lol_game_client_sln\\releases\\0.0.1.130\\deploy\\League of Legends.exe" "8394" "LoLLauncher.exe" "" "spectator <?php echo $spectator_url;?> <?php echo $game_key . " " . $game_id . " " . $platform;?>\"'
            });
        };

        </script>


    </body>
</html>
