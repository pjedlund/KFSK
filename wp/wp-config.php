<?php define('WP_CACHE', true);
/**
 * Baskonfiguration f�r WordPress.
 *
 * Denna fil inneh�ller f�ljande konfigurationer: Inst�llningar f�r MySQL,
 * Tabellprefix, S�kerhetsnycklar, WordPress-spr�k, och ABSPATH.
 * Mer information p� {@link http://codex.wordpress.org/Editing_wp-config.php 
 * Editing wp-config.php}. MySQL-uppgifter f�r du fr�n ditt webbhotell.
 *
 * Denna fil anv�nds av wp-config.php-genereringsskript under installationen.
 * Du beh�ver inte anv�nda webbplatsen, du kan kopiera denna fil direkt till
 * "wp-config.php" och fylla i v�rdena.
 *
 * @package WordPress
 */

// ** MySQL-inst�llningar - MySQL-uppgifter f�r du fr�n ditt webbhotell ** //
/** Namnet p� databasen du vill anv�nda f�r WordPress */
define('DB_NAME', 'u5165890_3');

/** MySQL-databasens anv�ndarnamn */
define('DB_USER', 'u5165890');

/** MySQL-databasens l�senord */
define('DB_PASSWORD', 'vonnegut#');

/** MySQL-server */
define('DB_HOST', 'mysql.u5165890.fsdata.se');

/** Teckenkodning f�r tabellerna i databasen. */
define('DB_CHARSET', 'utf8');

/** Kollationeringstyp f�r databasen. �ndra inte om du �r os�ker. */
define('DB_COLLATE', '');

/**#@+
 * Unika autentiseringsnycklar och salter.
 *
 * �ndra dessa till unika fraser!
 * Du kan generera nycklar med {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * Du kan n�r som helst �ndra dessa nycklar f�r att g�ra aktiva cookies obrukbara, vilket tvingar alla anv�ndare att logga in p� nytt.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'LS-2+Lt|QW%0jp{L30_AFn---b?F66|julRR:6sF2(n^8a`}kv!{DDD>0{c,p% L');
define('SECURE_AUTH_KEY',  '7X#.cUA`(R2~QQx;<Ys=4=1dzROv8ux?xkmD}pQ=9D@yBVOY9osD}l0E(Ff`?EDG');
define('LOGGED_IN_KEY',    'sm^<kmo-G,d)|oct3u}B}%S:j1{K@},x%u|*:V; |=.!/Y=kmruRj0.,GYvrrhj(');
define('NONCE_KEY',        'j>`,>ohey1[#V*,3N{|p2D48S6-4}fpmMVmaQv-J|N.IJ+E{tOPKEK`J[6J/<x$-');
define('AUTH_SALT',        '@c{K#s>^mGw]=M;.5xUf,M>G e}RCK|usJO>pF%v,5Pl-*z7Mr1|iAI%Xuhf/<6b');
define('SECURE_AUTH_SALT', 'Zs-W1UA|P&7Z+q]EF8cgUtukiVLGJX-9Y*[L!yIw;-}{S.{<r>*+CF1Zn.h1;)XQ');
define('LOGGED_IN_SALT',   '[nX@w!^jj11Tb9#Oat]?`FkI=&4F8X`(,z)91^^!n:ksM^Hq?4.(xuYdBEh;{(rV');
define('NONCE_SALT',       'p+/tT?A%AH FH86J~olPCFz3ci@s:%v5Uhm+-0Bn98#`W(To7[hMl46$$ c*PDVu');

/**#@-*/

/**
 * Tabellprefix f�r WordPress Databasen.
 *
 * Du kan ha flera installationer i samma databas om du ger varje installation ett unikt
 * prefix. Endast siffror, bokst�ver och understreck!
 */
$table_prefix  = 'wp_';

/**
 * WordPress-spr�k, f�rinst�llt f�r svenska.
 *
 * Du kan �ndra detta f�r att �ndra spr�k f�r WordPress.  En motsvarande .mo-fil
 * f�r det valda spr�ket m�ste finnas i wp-content/languages. Exempel, l�gg till
 * sv_SE.mo i wp-content/languages och ange WPLANG till 'sv_SE' f�r att f� sidan
 * p� svenska.
 */
define('WPLANG', 'sv_SE');

/** 
 * F�r utvecklare: WordPress fels�kningsl�ge. 
 * 
 * �ndra detta till true f�r att aktivera meddelanden under utveckling. 
 * Det �r rekommderat att man som till�ggsskapare och temaskapare anv�nder WP_DEBUG 
 * i sin utvecklingsmilj�. 
 */ 
define('WP_DEBUG', false);

/* Det var allt, sluta redigera h�r! Blogga p�. */

/** Absoluta s�kv�g till WordPress-katalogen. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Anger WordPress-v�rden och inkluderade filer. */
require_once(ABSPATH . 'wp-settings.php');