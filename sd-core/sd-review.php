<?php
if( !defined( 'SD_H_URL' ) ) { echo 'Direct access not allowed.';  exit; }

if(!defined('SDDB_REVIEWS')) define( 'SDDB_REVIEWS', 'sddb_reviews');

if(!class_exists('SD_REVIEW'))
{
	class SD_REVIEW
	{
		static public function db_structure()
		{
			global $wpdb;
			$charset_collate = $wpdb->get_charset_collate();
			return "CREATE TABLE ".$wpdb->prefix.SDDB_REVIEWS." (
                    product mediumint(9) NOT NULL,
                    ip VARCHAR(45) NOT NULL,
                    review TINYINT NOT NULL DEFAULT 1,
                    UNIQUE KEY id (product, ip)
                 ) $charset_collate;";
		} // End get_db_structure

		static public function set_review($id, $review)
		{
			global $wpdb;
			if(function_exists('sd_getIP'))
			{
				$ip = sd_getIP();
				$wpdb->query(
					$wpdb->prepare(
						"INSERT INTO ".$wpdb->prefix.SDDB_REVIEWS.
						" (product, ip, review) VALUES(%d, %s, %d) ON DUPLICATE KEY UPDATE review=%d",
						$id, $ip, $review, $review
					)
				);
				$row = self::get_review($id);
				if($row && isset($row['average']))
				{
					$wpdb->update($wpdb->prefix.SDDB_POST_DATA, array('popularity' => $row['average']), array('id' => $id), array('%d'), array('%d'));
				}
			}
		} // End set_review

		static public function get_review($id)
		{
			global $wpdb;
			return $wpdb->get_row(
				$wpdb->prepare(
					"SELECT FLOOR(AVG(review)) as average, COUNT(review) as votes FROM ".$wpdb->prefix.SDDB_REVIEWS.
					" WHERE product=%d",
					$id
				),
				ARRAY_A
			);
		} // End get_review

	} // End SD_REVIEW
}