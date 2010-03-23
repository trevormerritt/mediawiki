<?php

/**
 * @ingroup Language
 */
class LanguageZh_hans extends Language {
	function hasWordBreaks() {
		return false;
	}

	/**
	 * Eventually this should be a word segmentation;
	 * for now just treat each character as a word.
	 * @todo Fixme: only do this for Han characters...
	 */
	function segmentByWord( $string ) {
		$reg = "/([\\xc0-\\xff][\\x80-\\xbf]*)/";
		$s = self::insertSpace( $string, $reg );
		return $s;
	}

	function normalizeForSearch( $string ) {
		wfProfileIn( __METHOD__ );

		// Double-width roman characters
		$s = parent::normalizeForSearch( $s );
		$s = trim( $s );
		$s = self::segmentByWord( $s );

		wfProfileOut( __METHOD__ );
		return $s;
	}
}