<?php 

class FileDataInfo {

	private $field_names;
	private $cells_numbers;
	private const BUFFER_SIZE = 750;

	/**
	* Объект, для операции извлечения данных из CSV файлов
	* и последующей вставки извлеченных данных в указанную БД.
	*
	* @param PDO  $pdo          объект подключения к БД
	* @param string $table_name таблица БД
	* @param string $file_name  файл (CSV) с данными
	* @param int    $start_row  номер стартовой строки в файле
	* @param array  $columns    имена колонок таблицы с номерами колонок в CSV файле для них.
	*/
	public function __construct(
		private PDO $pdo,
		private string $table_name,
		private string $file_name,
		private int    $start_row,
		array $columns
	) {
		$this->field_names = array_keys($columns);
		$this->cells_numbers = array_values($columns);
	}

	private function getQueryTemplate(int $str_set_num=1): string
	{
		$values_template = implode(', ', array_fill(0, count($this->cells_numbers), '?'));
		$query_template = sprintf(
			"INSERT INTO %s (%s) VALUES %s",
			$this->table_name,
			implode(', ', $this->field_names),
			implode(', ', array_fill(0, $str_set_num, "($values_template)"))
		);
		return $query_template;
	}

	public function run()
	{
		try {
			$pdo = $this->pdo;
			
			if ( !in_array($this->table_name, $pdo->query("SHOW TABLES")->fetchall()[0]) ) {
				throw new Exception("Неверное имя таблицы '{$this->table_name}'", 1);
			}

			$db_fields = $pdo->query("SHOW COLUMNS FROM {$this->table_name}")
							->fetchall(PDO::FETCH_COLUMN, 0);

			foreach ($this->field_names as $field) {
				if (!in_array($field, $db_fields)) {
					throw new Exception("Ошибка! Неверное имя поля '{$field}'!", 1);
				}
			}

			if (false === ($fp = @fopen($this->file_name, 'r'))) {
				throw new Exception("Неудалось прочитать файл данных {$this->file_name}", 1);
			}
			print "Начинаю вставку данных...\n";
			
			$data_buffer = [];
			$buffer_state = 0;
			$stmh = $pdo->prepare($this->getQueryTemplate(self::BUFFER_SIZE));

			for ($i = 1; $row = fgets($fp); $i++) { # нумерация строк в файле начинаетс с 1.
				if ($i < $this->start_row) {
					continue;
				}
				$data = explode(',', $row);
				foreach ($this->cells_numbers as $num => $column) {
					$data_buffer[] = $data[$column];
				}
				$buffer_state++;
				if ($buffer_state == self::BUFFER_SIZE) {
					$stmh->execute($data_buffer);
					$data_buffer = [];
					$buffer_state = 0;
				}
			}
			if ($buffer_state > 0) {
				$pdo->prepare($this->getQueryTemplate($buffer_state))->execute($data_buffer);
			}
			// TODO: Добавить проверку был ли файл считан до конца.
			fclose($fp);
		} catch (PDOException $e) {
			print $e->getmessage();
		} catch (Exception $e) {
			print $e->getmessage();
		}
	}
}
