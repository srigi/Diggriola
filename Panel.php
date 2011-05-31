<?php

namespace Diggriola;

use Nette\Utils\Strings,
	Nette\Diagnostics\IBarPanel;

/**
 * @author Štěpán Svoboda
 * @author Michael Moravec
 * @author Patrik Votoček
 * @author Igor Hlina (srigi@srigi.sk)
 */
class Panel implements IBarPanel
{

	/**
	 * @var Diggriola\Panel singleton instance
	 */
	private static $_instance = null;

	/**
	 * @var array
	 */
	private $queries = array();

	// <editor-fold defaultstate="collapsed" desc="$platform">
	/**
	 * @var string
	 */
	private $platform = '';

	public function getPlatform() {
		return $this->platform;
	}
	public function setPlatform($platform) {
		$this->platform = $platform;
	}
	// </editor-fold>


	/**
	 * Instantiate using {@link getInstance()}; Diggriola\Panel is a singleton object.
	 *
	 * @return void
	 */
	public function __construct()
	{

	}


	/**
	 * Enforce singleton. Disallow cloning.
	 *
	 * @return void
	 */
	private function __clone()
	{

	}


	/**
	 * Create singleton instance
	 *
	 * @return Diggriola\Panel
	 */
	public static function getInstance()
	{
		if (null === self::$_instance) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}


	public function getId()
	{
		return 'NotORM';
	}


	/**
	 * @return string HTML code for Debugbar
	 */
	public function getTab()
	{
		return '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAAXNSR0IArs4c6QAAAHpJREFUOMvVU8ENgDAIBON8dgY7yU3SHTohfoQUi7FGH3pJEwI9oBwl+j1YDRGR8AIzA+hiAIxLsoOW1R3zB9Cks1VKmaQWXz3wHWEJpBbilF3wivxKB9OdiUfDnJ6Q3RNGyWp3MraytbKqjADkrIvhPYgSDG3itz/TBsqre3ItA1W8AAAAAElFTkSuQmCC">' . count($this->queries) . ' queries';
	}


	/**
	 * @return string HTML code for Debugbar detail
	 */
	public function getPanel()
	{
		if (count($this->queries) == 0) {
			return NULL;
		}

		$i = 0;
		$platform = $this->platform;
		$queries = $this->queries;

		ob_start();
		require_once __DIR__ . '/panel.phtml';
		return ob_get_clean();
	}


	public function logQuery($sql, array $params = NULL)
	{
		$this->queries[] = array('sql' => $sql, 'params' => $params);
	}


	public static function dump($sql)
	{
		$keywords1 = 'CREATE\s+TABLE|CREATE(?:\s+UNIQUE)?\s+INDEX|SELECT|UPDATE|INSERT(?:\s+INTO)?|REPLACE(?:\s+INTO)?|DELETE|FROM|WHERE|HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT|SET|VALUES|LEFT\s+JOIN|INNER\s+JOIN|TRUNCATE';
		$keywords2 = 'ALL|DISTINCT|DISTINCTROW|AS|USING|ON|AND|OR|IN|IS|NOT|NULL|LIKE|TRUE|FALSE|INTEGER|CLOB|VARCHAR|DATETIME|TIME|DATE|INT|SMALLINT|BIGINT|BOOL|BOOLEAN|DECIMAL|FLOAT|TEXT|VARCHAR|DEFAULT|AUTOINCREMENT|PRIMARY\s+KEY';

		// insert new lines
		$sql = " $sql ";
		$sql = Strings::replace($sql, "#(?<=[\\s,(])($keywords1)(?=[\\s,)])#", "\n\$1");
		if (strpos($sql, "CREATE TABLE") !== FALSE)
			$sql = Strings::replace($sql, "#,\s+#i", ", \n");

		// reduce spaces
		$sql = Strings::replace($sql, '#[ \t]{2,}#', " ");

		$sql = wordwrap($sql, 100);
		$sql = htmlSpecialChars($sql);
		$sql = Strings::replace($sql, "#([ \t]*\r?\n){2,}#", "\n");
		$sql = Strings::replace($sql, "#VARCHAR\\(#", "VARCHAR (");

		// syntax highlight
		$sql = Strings::replace($sql,
						"#(/\\*.+?\\*/)|(\\*\\*.+?\\*\\*)|(?<=[\\s,(])($keywords1)(?=[\\s,)])|(?<=[\\s,(=])($keywords2)(?=[\\s,)=])#s",
						function ($matches) {
							if (!empty($matches[1])) // comment
								return '<em style="color:gray">' . $matches[1] . '</em>';

							if (!empty($matches[2])) // error
								return '<strong style="color:red">' . $matches[2] . '</strong>';

							if (!empty($matches[3])) // most important keywords
								return '<strong style="color:blue">' . $matches[3] . '</strong>';

							if (!empty($matches[4])) // other keywords
								return '<strong style="color:green">' . $matches[4] . '</strong>';
						}
		);
		$sql = trim($sql);
		return '<pre class="dump">' . $sql . "</pre>\n";
	}

}
