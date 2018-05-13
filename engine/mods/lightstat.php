<?php
/**
 * Отображение статистики - сколько пользователей на сайте.
 *
 * Сделан по мотивам модуля: https://artem-malcov.ru/moduli_i_skripty/modul-statistiki-lightstat-20-final-release-dlya-dle
 * @author Sander <oleg.sandev@gmail.com>
 * @link https://sandev.pro/
 */

if (!defined('DATALIFEENGINE')) {
	die("Hacking attempt!");
}


// После первого запуска закомментировать или удалить строку ниже
$db->query("CREATE TABLE IF NOT EXISTS `lightstat_sander` ( `id` VARCHAR(32) NOT NULL , `time` INT(1) NOT NULL , `type` TINYINT(1) NOT NULL , PRIMARY KEY (`id`), INDEX (`type`)) ENGINE = MyISAM");


// Настройки модуля
$mod = [
	'update_time'  => 30,	//Интервал обновления блока на JS, раз в N секунд
	'update_limit' => 10,	//Максимальное количество обновлений

	'offline_time' => 5,	//Сколько минут бездействия считать пользователя оффлайном
	'cache_time'   => 5,	//Время кеширования, секунд. Поставить 0 чтобы не использовать кеш
];


/**
 * Определение бот это или обычный пользователь
 * @return integer  0 - гость, 2 - бот
 */
function isBot()
{
	$bots = [
		'rambler', 'googlebot', 'aport', 'yahoo', 'msnbot', 'turtle', 'mail.ru', 'omsktele',
		'yetibot', 'picsearch', 'sape.bot', 'sape_context', 'gigabot', 'snapbot', 'alexa.com',
		'megadownload.net', 'askpeter.info', 'igde.ru', 'ask.com', 'qwartabot', 'yanga.co.uk',
		'scoutjet', 'similarpages', 'oozbot', 'shrinktheweb.com', 'aboutusbot', 'followsite.com',
		'dataparksearch', 'google-sitemaps', 'appEngine-google', 'feedfetcher-google',
		'liveinternet.ru', 'xml-sitemaps.com', 'agama', 'metadatalabs.com', 'h1.hrn.ru',
		'googlealert.com', 'seo-rus.com', 'yaDirectBot', 'yandeG', 'yandex',
		'yandexSomething', 'Copyscape.com', 'AdsBot-Google', 'domaintools.com',
		'Nigma.ru', 'bing.com', 'dotnetdotcom'
	];
	foreach ($bots as $bot) {
		if (stripos($_SERVER['HTTP_USER_AGENT'], $bot) !== false) {
			return 2;
		}
	}
	return 0;
}


$mt = microtime(1);
$time = time();
$mod['offline_time'] *= 60;


/**
 * Определение типа посетителя
 * 0 - гость
 * 1 - авторизованный
 * 2 - бот
 */
$type_user = $is_logged ? 1 : isBot();

$user_id = md5($_SERVER['REMOTE_ADDR']);
if ($db->query("SELECT id FROM lightstat_sander WHERE id = '$user_id'")->num_rows) {
	$db->query("UPDATE `lightstat_sander` SET time = $time WHERE id = '$user_id'");
} else {
	$db->query("INSERT INTO `lightstat_sander` VALUES ('$user_id', $time, $type_user)");
}

if ($mod['cache_time']) {
	$mod_cache = get_vars('lightstats_sander');
} else {
	$mod_cache = [];
}

if (!$mod['cache_time'] || $mod_cache['time'] + $mod['cache_time'] < $time) {
	$db->query("DELETE FROM `lightstat_sander` WHERE `time` + {$mod['offline_time']} < $time");
	$stats = [0, 0, 0];
	$sql = $db->query("SELECT count(*) as count, type FROM `lightstat_sander` GROUP BY type");
	while ($row = $db->get_row($sql)) {
		$stats[$row['type']] = $row['count'];
	}
	if ($mod['cache_time']) {
		set_vars('lightstats_sander', [
			'time'  => $time,
			'stats' => $stats,
		]);
	}
} else {
	$stats = $mod_cache['stats'];
}

$total_count = array_sum($stats);
$bar_width = [
	floor(100 * $stats[0] / $total_count),
	ceil(100 * $stats[1] / $total_count),
];

?>
<style>
.lightstat_sander {
	min-width: 200px;
	font-family: Arial;
	font-size: 12px;
	border-radius: 3px;
	overflow: hidden;
}
.lightstat_sander_head {
	background: #ddd;
	color: #222;
	text-align: center;
	padding: 30px 0 33px 0;
	color: #888;
}
.lightstat_sander_head_count {
	font-size: 26px;
	color: #222;
}
.lightstat_sander_bar {
	height: 5px;
	background: #619505;
}
.lightstat_sander_bar>div {
	float: left;
	height: 100%;
}
.lightstat_sander_bar_users {
	background: #d95e01;
}
.lightstat_sander_bar_guest {
	background: #af291d;
}
.lightstat_sander_body {
	background: #222;
	color: #888;
	padding: 20px 10px 20px 30px;
	text-shadow: 0 -1px 0 rgba(0, 0, 0, .6);
}
.lightstat_sander_body b {
	font-weight: 500;
	color: #fff;
}
.lightstat_sander_body > b {
	font-size: 10px;
	text-transform: uppercase;
}
.lightstat_sander_body_item {
	font-size: 13px;
	line-height: 20px;
	margin-top: 10px;
	position: relative;
	padding-right: 30px;
}
.lightstat_sander_body_item::before {
	content: '';
	position: absolute;
	top: 6px;
	left: -18px;
	width: 8px;
	height: 8px;
	background: #af291d;
	border-radius: 50%;
}
.lightstat_sander_body_item_users::before {
	background: #d95e01;
}
.lightstat_sander_body_item_bots::before {
	background: #619505;
}
.lightstat_sander_body_item > b {
	position: absolute;
	top: 0;
	right: 0;
}
</style>
<div class="lightstat_sander" id="lightstat_sander">
	<div class="lightstat_sander_head">
		<div class="lightstat_sander_head_count"><?= number_format($total_count, 0, '', ' ') ?></div>
		<?= $tpl->declination(['', $total_count, 'посетител|ь|я|ей']) ?> сейчас на сайте
	</div>
	<div class="lightstat_sander_bar">
		<div class="lightstat_sander_bar_users" style="width: <?= $bar_width[1] ?>%"></div>
		<div class="lightstat_sander_bar_guest" style="width: <?= $bar_width[0] ?>%"></div>
	</div>
	<div class="lightstat_sander_body">
		<b>из них:</b>
		<? if ($stats[1]): ?><div class="lightstat_sander_body_item lightstat_sander_body_item_users">Пользователи<b><?= number_format($stats[1], 0, '', ' ') ?></b></div><? endif; ?>
		<? if ($stats[0]): ?><div class="lightstat_sander_body_item lightstat_sander_body_item_guest">Гости<b><?= number_format($stats[0], 0, '', ' ') ?></b></div><? endif; ?>
		<? if ($stats[2]): ?><div class="lightstat_sander_body_item lightstat_sander_body_item_bots">Роботы<b><?= number_format($stats[2], 0, '', ' ') ?></b></div><? endif; ?>
	</div>
</div>

<script>
var lightstat_counter = 0;
var lightstat_interval = setInterval(function(){
	$("#lightstat_sander").load(window.location.pathname + window.location.search + " #lightstat_sander");
	lightstat_counter++;
	if (lightstat_counter > <?= $mod['update_limit'] ?>) {
		clearInterval(lightstat_interval);
	}
}, <?= $mod['update_time'] ?>000);
</script>
