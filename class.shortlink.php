<?php

/**
 * A simple base class for creating short links
 */
class ShortLink
{
	
	private $hostname,
			$username,
			$password,
			$database,
			$db;
	
	function __construct($hostname, $username, $password, $database)
	{
		$this->hostname = $hostname;
		$this->username = $username;
		$this->password = $password;
		$this->database = $database;
	}

	
	/**
	 * This method will check if a connection to the MySQL server can be made,
	 * if not it will throw an exception
	 */
	protected function checkConnection()
	{
		$this->db = new mysqli($this->hostname, $this->username, $this->password, $this->database);
		
		if ($this->db->connect_errno) {
			throw new Exception('Error connecting to database - ' . $this->db->connect_error);
		}
	}

	/**
	 * This method will take a url, and create a short link based on a random shuffle of 5 characters
	 * @param  [string] $url [URL: eg. http://madsobel.com]
	 * @return [string]      [The shortend string matching the param giving]
	 */
	public function createShortLink($url)
	{
		$this->checkConnection();

		$short = $this->generateShortLink();

		if (!($stmt = $this->db->prepare('INSERT INTO urls (link, short) VALUES (?, ?)'))) {
			throw new Exception('Could not prepare MySQL statement');
		}

		if (!$stmt->bind_param("ss", $url, $short)) {
			throw new Exception('Could not bind the parameters');
		}

		if (!$stmt->execute()) {
			throw new Exception('Could not execute the query');			
		}

		$stmt->close();

		return $short;
	}

	/**
	 * This method will get the matching 'real' url to a short link giving
	 * @param  [string] $short [Short link eg. YaMl6]
	 * @return [string]        ['Real URL' eg. http://madsobel.com]
	 */
	public function getShortLink($short)
	{
		$this->checkConnection();

		if (!($stmt = $this->db->prepare('SELECT link FROM urls WHERE short = ?'))) {
			throw new Exception('Could not prepare MySQL statement');
		} else {
			$stmt->bind_param("s", $short);

			$stmt->execute();

			$stmt->bind_result($dist);

			$stmt->fetch();

			$stmt->close();

			return $dist;
		}

	}

	/**
	 * This method will generate a unique short link that doesn't exist already
	 * @param  [string] $shortCharacters [Characters to use in the generation]
	 * @param  [string] $shortLength [Max length of the short link]
	 * @return [string] $short ['Real URL' eg. http://madsobel.com]
	 */
	public function generateShortLink($shortCharacters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', $shortLength = 5)
	{
		do {
			$short = substr(str_shuffle($shortCharacters), 0, $shortLength);
		} while (!empty($this->getShortLink($short)));

		return $short;
	}

}