<?php 

class PDOInstance {
	
	public static array $instances = [];
	
	/**
	 * Возвращает существующий или создает новый объект PDO.
	 * @param  string $host хост БД
	 * @param  string $db   название БД
	 * @param  string $user логин пользователя
	 * @param  string $pass пароль пользователя
	 * @return PDO          объект подключения к БД
	 */
	static public function get(string $host, string $db, string $user, string $pass): PDO {
		
		$params = [$host, $db, $user];
		$instances = &self::$instances;
		$paramscount = count($params);
		$last_index = $paramscount - 1;
		
		for ($i = 0; $i < $paramscount; $i++) {
			$p = $params[$i];
			// Погружение на уровень.
			if (isset($instances[$p])) {
				if ($i == $last_index) {
					return $instances[$p];
				}
				$instances = &$instances[$p];
			// иначе создается уровень.
			} else {
				if ($i == $last_index) {
					$conn_options = sprintf(
						"mysql:host=%s;dbname=%s;charset=utf8", 
						$host,
						$db
					);	
					$pdo = new PDO($conn_options, $user, $pass);
					$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_NUM);
					return $instances[$p] = $pdo;
				}
				$instances[$p] = [];
				$instances = &$instances[$p];
			}
		}
	}
}
