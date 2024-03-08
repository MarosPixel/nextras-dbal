<?php declare(strict_types = 1);

/** @testCase */

namespace NextrasTests\Dbal;


use Mockery\MockInterface;
use Nextras\Dbal\Exception\InvalidArgumentException;
use Nextras\Dbal\Platforms\IPlatform;
use Nextras\Dbal\SqlProcessor;
use Tester\Assert;


require_once __DIR__ . '/../../bootstrap.php';


class SqlProcessorValuesTest extends TestCase
{
	/** @var IPlatform|MockInterface */
	private $platform;

	/** @var SqlProcessor */
	private $parser;


	protected function setUp()
	{
		parent::setUp();
		$this->platform = \Mockery::mock(IPlatform::class);
		$this->parser = new SqlProcessor($this->platform);
	}


	public function testArray()
	{
		$this->platform->shouldReceive('formatString')->once()->with("'foo'")->andReturn("'\\'foo\\''");
		$this->platform->shouldReceive('formatIdentifier')->once()->with('id')->andReturn('id');
		$this->platform->shouldReceive('formatIdentifier')->once()->with('title')->andReturn('title');
		$this->platform->shouldReceive('formatIdentifier')->once()->with('foo')->andReturn('foo');

		Assert::same(
			"INSERT INTO test (id, title, foo) VALUES (1, '\\'foo\\'', 2)",
			$this->convert('INSERT INTO test %values', [
				'id%i' => 1,
				'title%s' => "'foo'",
				'foo' => 2,
			])
		);
	}


	public function testMultiInsert()
	{
		$this->platform->shouldReceive('formatIdentifier')->once()->with('id')->andReturn('id');
		$this->platform->shouldReceive('formatIdentifier')->once()->with('title')->andReturn('title');
		$this->platform->shouldReceive('formatIdentifier')->once()->with('foo')->andReturn('foo');

		$this->platform->shouldReceive('formatString')->once()->with("'foo'")->andReturn("'\\'foo\\''");
		$this->platform->shouldReceive('formatString')->once()->with("'foo2'")->andReturn("'\\'foo2\\''");

		Assert::same(
			"INSERT INTO test (id, title, foo) VALUES (1, '\\'foo\\'', 2), (2, '\\'foo2\\'', 3)",
			$this->convert('INSERT INTO test %values[]', [
				[
					'id%i' => 1,
					'title%s' => "'foo'",
					'foo' => 2,
				],
				[
					'id%i' => 2,
					'title%s' => "'foo2'",
					'foo' => 3,
				],
			])
		);
	}


	public function testInsertWithDefaults()
	{
		Assert::same(
			"INSERT INTO test VALUES (DEFAULT)",
			$this->convert('INSERT INTO test %values', [])
		);

		Assert::same(
			"INSERT INTO test VALUES (DEFAULT)",
			$this->convert('INSERT INTO test %values[]', [[]])
		);

		Assert::same(
			"INSERT INTO test VALUES (DEFAULT)",
			$this->convert('INSERT INTO test %values[]', [1 => []])
		);

		Assert::same(
			"INSERT INTO test VALUES (DEFAULT), (DEFAULT)",
			$this->convert('INSERT INTO test %values[]', [[], null])
		);

		$this->platform->shouldReceive('formatIdentifier')->once()->with('id')->andReturn('id');
		$this->platform->shouldReceive('formatIdentifier')->once()->with('author')->andReturn('author');

		Assert::same(
			"INSERT INTO test (id, author) VALUES (1, 2), (DEFAULT, DEFAULT), (DEFAULT, DEFAULT)",
			$this->convert('INSERT INTO test %values[]', [['id' => 1, 'author' => 2], null, null])
		);

		Assert::throws(function () {
			$this->convert('INSERT INTO test %values[]', []);
		}, InvalidArgumentException::class, 'Modifier %values[] must contain at least one array element.');
	}


	private function convert($sql)
	{
		return $this->parser->process(func_get_args());
	}
}


$test = new SqlProcessorValuesTest();
$test->run();
