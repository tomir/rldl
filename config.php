<?php
class C {
	/* SQL */
	const SQL_USER='root';
	const SQL_PASS='';
	const SQL_SERV='localhost';
	const SQL_DB='realdeal';
	/* PAYLANE setting */
	const PAYLANE_USER='tryrealdeal';
	const PAYLANE_PASS='ga7cri7s';
	const PAYLANE_METHODS='(json)["paypal","card"]';
	const DEFAULT_CURRENCY='EUR'; // or 'eur' ? 
	/* cloud storage buckets */
	const GSBUCKET_IMAGES='rd-images';
	const GSBUCKET_STATIC='rd-static';
	const GSBUCKET_AVATARS='rd-avatars';
	const GSBUCKET_TEMP='rd-temp';
	/* upload img max size */
	const IMG_MAX_SIZE='2';
	const IMG_DIR='gs://rd-images';
	/* facebook app settings */
	const FB_APP_ID='(string)168903756627211';
	const FB_APP_SECRET='(string)61bea5e221b1b8fe470ef74fc86acb00';
	/* google app settings */
	const GP_CLIENT_ID='(string)700809255542.apps.googleusercontent.com';
	const GP_CLIENT_SECRET='(string)OSSjQ9se4Acst4TDzlfdXyhh';
	const GP_DEV_KEY='(string)AIzaSyAvw4mdq8hkKQZfY9KEwPsWtGaBr92Osew';
	const GP_APP_NAME='Real Deal';
	/* super admin id */
	const ADMINS='(json)[1,31]';
	/* active langs */
	const LOCALES='(json)["pl_PL","en_US","es_ES","es_LA","pt_BR","pt_PT"]';
	const DEFAULT_LOCALE='en_US';
	/* other */
	const APP_TERMS_URL='http://tryrealdeal.com/terms';
	const URL_AVATARS='https://cdn.rldl.net/avatars/{{user_id}}.png';
	const URL_TERMS='http://cdn.rldl.net/documents/terms/{{campaign_id}}';
	const URL_CAMPAIGN='https://realde.al/{{campaign_id}}';
	const URL_DEAL='https://realde.al/{{campaign_id}}/{{deal_id}}';
	const URL_VARIANT='https://realde.al/{{campaign_id}}/{{deal_id}}';
	const URL_DEAL_FRAME='https://go.rldl.net/{{data}}';
	const USER_ACCOUNT_URL='https://privacy.rldl.net';
	const URL_APP='https://realdealapp.com';
	const URL_APP_INVITE="https://realdealapp.com/client/{{client_id}}!invite={{invite}}";
	/* post const */
	const POST_UID_CAMPAIGN='Campaign-{{campaign_id}}-{{user_id}}';
	const POST_UID_DEAL='Deal-{{deal_id}}-{{user_id}}';
	const POST_UTM='utm_source={{platform}}&utm_medium=post&utm_campaign=user-{{user_id}}';
	/* mail */
	const MAIL_FROM='noreply@tryrealdeal.com';
	const MAIL_REPLYTO='support@tryrealdeal.com';
	const MAIL_NAME='Help';
	const NAME='Realdeal';
	/* invite def */
	const INVITE_TYPE=2;
	const INVITE_DAYS=7;
	const INVITE_DAYS_MAX=365;
	/* cron */
	const CRON_HEADER='x-appengine-cron';
	const CRON_TIMELIMIT=500;
	const ANNOUNCEMENT_DAYS=3;
	const CLIENT_MAIL_DAYS=7;
	const CLIENT_MAIL_CAMPAIGN_COUNT=10;
	/* maps */
	const GOOGLE_MAPS_KEY="AIzaSyBZGFasgZlp-SAO3JYJRKODc1dsGGDzwRs";
	/* ga */
	const GA_KEY="(file)RD1-0a65f71af4ad.p12";
	const GA_APP_NAME="RD1";
	const GA_MAIL="100921244920-cq6h36jnuqjujootuomua20kffsq9aha@developer.gserviceaccount.com";
	const GA_CLIENT_ID="100921244920-cq6h36jnuqjujootuomua20kffsq9aha.apps.googleusercontent.com";
	const GA_IDS="ga:92980157";
	/* client account def sets */
	const CLIENT_DAYS_TO_LOCK=14;
	const CLIENT_DEFAULT_TEMPLATE="default";
}
?>