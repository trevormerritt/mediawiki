<?php

use PHPUnit\Framework\AssertionFailedError;

/**
 * @covers MockHttpTrait
 * @group MediaWikiIntegrationTestCaseTest
 */
class MockHttpTraitTest extends MediaWikiIntegrationTestCase {
	use MockHttpTrait;

	public function testInstallMockHttpPreventsRequests() {
		$this->installMockHttp();

		try {
			$this->getServiceContainer()->getHttpRequestFactory()
				->create( 'http://example.com' );
			$pass = false;
		} catch ( AssertionFailedError $ex ) {
			$pass = true;
		}

		$this->assertTrue( $pass, 'HTTP request prevented' );
	}

	public function testMakeMockHttpRequestFactoryPreventsRequests() {
		try {
			$this->makeMockHttpRequestFactory()->create( 'http://example.com' );
			$pass = false;
		} catch ( AssertionFailedError $ex ) {
			$pass = true;
		}

		$this->assertTrue( $pass, 'HTTP request prevented' );
	}

	public function provideFactoryRequestData() {
		yield 'a request object' => [ $this->makeFakeHttpRequest( 'Hello World' ), 'Hello World' ];

		yield 'just a string' => [ 'Hello World', 'Hello World' ];

		yield 'a closure returning a request' => [
			function () {
				return $this->makeFakeHttpRequest( 'Hello World' );
			},
			'Hello World'
		];

		yield 'a list of requests' => [
			[
				$this->makeFakeHttpRequest( 'Hello World' ),
				$this->makeFakeHttpRequest( 'Yadda Yadda' ),
			],
			'Hello World'
		];
	}

	/**
	 * @dataProvider provideFactoryRequestData
	 */
	public function testInstallMockHttpEmulatesRequests( $request, $expected ) {
		$this->installMockHttp( $request );

		$data = $this->getServiceContainer()->getHttpRequestFactory()
			->get( 'http://example.com' );

		$this->assertSame( $expected, $data );
	}

	/**
	 * @dataProvider provideFactoryRequestData
	 */
	public function testMakeMockHttpRequestFactoryEmulatesRequests( $request, $expected ) {
		$data = $this->makeMockHttpRequestFactory( $request )
			->get( 'http://example.com' );

		$this->assertSame( $expected, $data );
	}

	public function testFakeHttpRequestEmulatesRequests() {
		$client = $this->makeFakeHttpRequest( 'Hello World' );

		$called = 0;
		$callback = function ( $resource, $buffer ) use ( &$called ) {
			$this->assertSame( 'Hello World', $buffer );
			$called++;
			return strlen( $buffer );
		};

		$client->setCallback( $callback );
		$client->execute();

		$this->assertSame( 1, $called, 'Callback was called' );
		$this->assertSame( 'Hello World', $client->getContent() );
	}

	public function provideMultiRequestData() {
		yield [
			[
				'a' => [ 'url' => 'http://a.example.com', ],
				[ 'url' => 'http://b.example.com', ],
				[ 'url' => 'http://c.example.com', ],
			],
			[
				'a' => 'Hello World',
				[
					'code' => 404,
					'body' => 'not found'
				],
				[
					'response' => [
						'code' => 0,
						'error' => 'timeout'
					]
				]
			],
			[
				'a' => [
					'url' => 'http://a.example.com',
					'response' => [
						// specifics
						'body' => 'Hello World',

						// defaults
						'code' => 200,
						'reason' => '',
						'headers' => [],
						'error' => '',

						// numeric keys
						0 => 200, // code
						1 => '', // reason
						2 => [], // headers
						3 => 'Hello World', // body
						4 => '', // error
					],
				],
				[
					'url' => 'http://b.example.com',
					'response' => [
						// specifics
						'code' => 404,
						'body' => 'not found',

						// defaults
						'reason' => '',
						'headers' => [],
						'error' => '',

						// numeric keys
						0 => 404, // code
						1 => '', // reason
						2 => [], // headers
						3 => 'not found', // body
						4 => '', // error
					],
				],
				[
					'url' => 'http://c.example.com',
					'response' => [
						// specifics
						'code' => 0,
						'error' => 'timeout',

						// defaults
						'reason' => '',
						'headers' => [],
						'body' => '',

						// numeric keys
						0 => 0, // code
						1 => '', // reason
						2 => [], // headers
						3 => '', // body
						4 => 'timeout', // error
					],
				],
			]
		];
	}

	/**
	 * @dataProvider provideMultiRequestData
	 */
	public function testFakeHttpMultiClientEmulatesRequests( $requests, $responses, $expected ) {
		$client = $this->makeFakeHttpMultiClient( $responses );

		$data = $client->runMulti( $requests );
		$this->assertSame( $expected, $data );
	}

	/**
	 * @dataProvider provideMultiRequestData
	 */
	public function testInstallMockHttpEmulatesMultiClient( $requests, $responses, $expected ) {
		$client = $this->makeFakeHttpMultiClient( $responses );
		$this->installMockHttp( $client );

		$client = $this->getServiceContainer()->getHttpRequestFactory()
			->createMultiClient();

		$data = $client->runMulti( $requests );
		$this->assertSame( $expected, $data );
	}

	/**
	 * @dataProvider provideMultiRequestData
	 */
	public function testMakeMockHttpRequestFactoryEmulatesMultiClient(
		$requests, $responses, $expected
	) {
		$client = $this->makeFakeHttpMultiClient( $responses );
		$client = $this->makeMockHttpRequestFactory( $client )
			->createMultiClient();

		$data = $client->runMulti( $requests );
		$this->assertSame( $expected, $data );
	}

}
