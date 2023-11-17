# MemberDash automated tests

This document describes how to deal with the MemberDash automated tests.

## Table of contents

- [MemberDash automated tests](#memberdash-automated-tests)
    - [Table of contents](#table-of-contents)
    - [How to setup the tests environment](#how-to-setup-the-tests-environment)
        - [slic installation](#slic-installation)
        - [slic configuration](#slic-configuration)
    - [How to run tests](#how-to-run-tests)
    - [Main test tools](#main-test-tools)
        - [WP factory](#wp-factory)
    - [Helpful traits](#helpful-traits)
        - [`WithFaker`](#withfaker)
        - [`WithHelper`](#withhelper)
        - [`WithHooks`](#withhooks)
        - [`WithMocker`](#withmocker)
        - [`WithUopz`](#withuopz)
            - [Tracking function calls](#tracking-function-calls)
            - [Mocking PHP built-in functions](#mocking-php-built-in-functions)
    - [Tests organization](#tests-organization)
        - [WPUnit suite (`wpunit` directory)](#wpunit-suite-wpunit-directory)
        - [Integration suite (`integration` directory)](#integration-suite-integration-directory)
        - [Snapshot suite (`snapshot` directory)](#snapshot-suite-snapshot-directory)
        - [Acceptance suite (`acceptance` directory)](#acceptance-suite-acceptance-directory)
        - [AcceptanceJS suite (`acceptancejs` directory)](#acceptancejs-suite-acceptancejs-directory)
    - [How to write testable code](#how-to-write-testable-code)
    - [References](#references)



## How to setup the tests environment

### slic installation

In order to run integration and acceptance tests, we need to setup a WordPress test environment. Luckily, there is a tool called [slic](https://github.com/stellarwp/slic) that can help us with that.

First of all, make sure you have installed in your machine:

- [Docker](https://docs.docker.com/get-docker/)
- The `mysql` module for PHP
- The **sendmail** application (optional)

In a Linux environment, you can install the `sendmail` application and the `mysql` module for PHP by installing the `sendmail` and `php-mysql` packages.

Then, install the `slic` tool by following the instructions in the [slic repository](https://github.com/stellarwp/slic#installation). After that, check if you are able to run the `slic help` command in your terminal.

### slic configuration

1) Navigate to the **plugins** directory of your WordPress installation (e.g. /var/www/html/wp-content/plugins)
2) Run `slic here`
3) Run `slic use memberdash` to select the MemberDash plugin as a target
4) Run `slic composer install` to install the dependencies

## How to run tests

Before running tests, make sure you have the dependencies up-to-date. Please follow the instructions on **Setup for development** in [CONTRIBUTING.md](../CONTRIBUTING.md#setup-for-development) file.

If while running tests you encounter an error telling you that you're running an unsupported version of PHPUnit, ensure that you don't have a standalone copy of PHPUnit installed that could be getting loaded during your tests. 

As an example, if you have [ld-licensing](https://bitbucket.org/learndash/ld-licensing) cloned to your machine as `learndash-hub` it will be included automatically while running tests that don't use the bundled database (such as integration tests). If you have an old enough version of `ld-licensing` checked out, [it could be including its own version of PHPUnit](https://bitbucket.org/learndash/ld-licensing/commits/92e91f59f88dcd21ad667ad5357de18b40141789) and may be the culprit.

| command                                             | description                                                                                        |
| --------------------------------------------------- | -------------------------------------------------------------------------------------------------- |
| `composer test`                                     | Runs all automated MemberDash tests.                                                               |
| `composer test <test_path>`                         | Runs all tests in a directory or file.                                                             |
| `composer test <test_file_path>:<test_method_name>` | Runs the exact test in a file.                                                                     |
| `composer test:wpunit`                              | Runs only the wpunit tests (MemberDash + WP).                                                      |
| `composer test:acceptance`                          | Runs only the acceptance tests (without JS).                                                       |
| `composer test:acceptancejs`                        | Runs only the acceptance tests (with JS).                                                          |
| `composer test:integration`                         | Runs only the integration tests (MemberDash + WP + DB).                                            |
| `composer test:snapshot`                            | Runs only the snapshot tests (MemberDash + WP + DB).                                               |
| `composer test:wpunit:debug`                        | Runs the wpunit tests in debug mode. Use the `codecept_debug` function to output something.        |
| `composer test:acceptance:debug`                    | Runs the acceptance tests in debug mode. Use the `codecept_debug` function to output something.    |
| `composer test:acceptancejs:debug`                  | Runs the acceptance JS tests in debug mode. Use the `codecept_debug` function to output something. |
| `composer test:integration:debug`                   | Runs the integration tests in debug mode. Use the `codecept_debug` function to output something.   |
| `composer test:snapshot:debug`                      | Runs the snapshot tests in debug mode and also **updates the current snapshots**.                  |
| `composer test:repeat`                              | Runs only the failed tests.                                                                        |
| `composer test:clean`                               | Cleans the tests cache directory and slic containers.                                              |

## Main test tools

- The [`uopz` PHP extension](https://www.php.net/manual/en/book.uopz.php) to mock functions or class methods;
- The [Mockery](https://github.com/mockery/mockery) to mock objects;
- The [FakerPHP](https://github.com/FakerPHP/Faker/) to generate random data for the tests.

### WP factory

The WordPress [provides a factory](https://make.wordpress.org/core/handbook/testing/automated-testing/writing-phpunit-tests/#fixtures-and-factories) that allows us to create WordPress objects (posts, terms, users, etc.) in a simple way. It is very useful to create the test data. You can access the factory by using the `$this->factory()` method into a test class. See some examples below:

```php
// Create a post.
$post_id = $this->factory()->post->create(
			array(
				'post_title'   => 'My post',
				'post_content' => 'My post content',
			)
		);

// Create and get a post.
$post = $this->factory()->post->create_and_get(
			array(
				'post_title'   => 'My post',
				'post_content' => 'My post content',
			)
		);

// Create a user.
$user = $this->factory()->user->create_and_get();

// Create three tags.
$tags = $this->factory()->term->create_many(
			3,
			array(
				'taxonomy' => 'post_tag',
			)
		);
```

In addition to the WordPress factory methods, we have the `create_many_and_get()` method, accessed by using the `WithHelper`, that allows us to create multiple objects and get them in a single call. See the example below:

```php
...
use WithHelper;
...
// Create 10 users and get them.
$users = $this->helper->create_many_and_get( 10, $this->factory()->user );
```

## Helpful traits

In order to help us to write tests, we have created a set of `traits` that can be used in the tests. You can find them in the `tests/_support/Trait` directory.

**NOTE**: Mockery and uopz **don't work** with acceptance tests!

The traits are described below:

### `WithFaker`

This trait provides the `$faker` property that can be used to generate random data.

```php
...
use WithFaker;
...

public function test_something() {
	// Generate a random single word.
	$word = $this->faker->word();
}
```

You can check all available methods in the [FakerPHP documentation](https://fakerphp.github.io/).

**Note**: you can't use the `$faker` property in [PHPUnit data provider methods](https://phpunit.readthedocs.io/en/9.5/writing-tests-for-phpunit.html#data-providers). If you need to generate random data in a data provider method, you can use the `Faker\Factory::create()` method:

```php
...
use Faker\Factory;
...
public function data_provider_test() {
	$faker = Factory::create();
	$word = $this->faker->word();
}
```

### `WithHelper`

This trait provides some useful methods to deal with repeatable tasks in the tests. Take a look at the [`tests/_support/Traits/WithHelper.php`](_support/Traits/Helper.php) file to see all available methods.

### `WithHooks`

This trait provides an `applied_filter()` method that can be used to check if a filter has been applied N times. Example:

```php
...
use WithHooks;
...
public function test_something() {
	// run the code that should apply the filter.

	// check if the filter 'my_filter' has been applied 2 times.
	$this->assertSame(
		2,
		$this->applied_filter( 'my_filter' )
	);
}
```

And an `applied_filter_args()` method that can be used to check if a filter has been applied with correct arguments. Example:

```php
...
use WithHooks;
...
public function test_something() {
	// run the code that applies the filter.

	// check if the filter 'my_filter' has been applied with the correct arguments.
	$this->assertSame(
		[ 'arg1_value', 'arg2_value' ],
		$this->applied_filter_args( 'my_filter' )
	);
}

// If you called the filter multiple times, you may want to check what arguments were passed to the filter in each call.
public function test_something() {
	// run the code that applies the filter more than 1 time.

	// check if the filter 'my_filter' has been applied with the correct arguments for the first time.
	$this->assertSame(
		[ 'call_1_arg1_value', 'call_1_arg2_value' ],
		$this->applied_filter_args( 'my_filter', 1 )
	);

	// check if the filter 'my_filter' has been applied with the correct arguments for the second time.
	$this->assertSame(
		[ 'call_2_arg1_value', 'call_2_arg2_value' ],
		$this->applied_filter_args( 'my_filter', 2 )
	);
}
```

### `WithMocker`

This trait provides the `$mocker` property that can be used to mock classes. Example:

```php
...
use WithMocker;
...
public function test_something() {
	// mock the entire class MemberDash_Action_Scheduler to be used as dependency.
	$mock_scheduler = $this->mocker->mock( 'MemberDash_Action_Scheduler' );

	// mock the needed class methods.
	$mock_scheduler
		->shouldReceive( 'register_callback' )
		->once()
		->with(
			'memberdash_cloning_membership',
			$this->mocker->any(),
			$this->mocker->any(),
			$this->mocker->any()
		)
		->andReturn( true );

	// pass the mocked class as dependency.
	$membership_cloning = new MemberDash_Membership_Cloning( $mock_scheduler );
}
```

See the [Mockery documentation](https://docs.mockery.io/en/latest/index.html) for more information.

### `WithUopz`

This trait provides some useful methods to manipulate the `uopz` PHP extension. Some examples:

```php
...
use WithUopz;
...
function test_foo_function() {
	// change the return value of the WP function is_admin to true.
	$this->uopz_set_function_return( 'is_admin', true );

	// you can also pass a closure to be executed when the function is called.
	$this->uopz_set_function_return( 'is_admin', function() {
		if(condition) {
			return true;
		}
		return false;
	} );

	// change a class method return value.
	$this->uopz_set_class_method_return(
		MemberDash_Action_Scheduler::class,
		'get_setting',
		'yes'
	);
}
```

#### Tracking function calls

We have the `track_function_call` and the `get_function_times_called` methods that can be used to check how many times a function has been called. Example:

```php
...
use WithUopz;
...
function test_foo_function() {
	// Arrange.

	// Register the intention to track the times the get_object_taxonomies function is called.
	$this->track_function_call( 'get_object_taxonomies' );

	// Assert.

	// Check if the get_object_taxonomies function has been called 2 times.
	$this->assertSame( 2, $this->get_function_times_called( 'get_object_taxonomies' ) );
}
```

#### Mocking PHP built-in functions

If you need to mock a PHP built-in function, you need to mock the function in the `setUp` method of the test class. You can use a filter to allow the other class methods to change the returned value of the mocked function. Example:

```php
...
use WithUopz;
...
protected function setUp(): void {
	parent::setUp();

	$this->uopz_set_function_return(
		'file_exists',
		function( $filename ) {
			// If the filename is not the mocked one, return the original function result.
			if ( 'mocked_file' !== $filename ) {
				return \file_exists( $filename );
			}

			// Otherwise, return true.
			return true;
		}
	);

	$this->uopz_set_function_return(
		'file_get_contents',
		function ( $filename ) {
			// If the filename is not the mocked one, return the original function result.
			if ( $filename !== 'php://input' ) {
				return \file_get_contents( $filename );
			}

			// Otherwise, return a filterable value to allow the other class methods to change it.
			return apply_filters( 'memberdash_stripe_gateway_test_file_get_contents', '' );
		}
	);
}
...
function test_foo_function() {
	// Add a filter to change the returned value of the mocked function.
	add_filter(
		'memberdash_stripe_gateway_test_file_get_contents',
		function () {
			return 'not json';
		}
	);
}
```

## Tests organization

The tests are organized in the `tests` directory, inside suites. Suites are independent groups of tests with a common purpose.

### WPUnit suite (`wpunit` directory)

That suite contains general tests. It's a kind of unit tests, but it's executed in a WordPress environment. We must have the same structure of the MemberDash plugin.

A test file should be named as a tested file, with following changes:

- Use the **PascalCase** naming convention
- Replace `-` and `_` to `<empty string>`
- Remove `class` prefix if it exists
- Add `Test` suffix

Example: `class-ms-helper-cast.php` becomes `MsHelperCastTest.php`.

Test methods should be named like `test_function_name` or `test_function_name_when_this_case_is_presented`.

### Integration suite (`integration` directory)

That suite contains integration tests. It's similar to the wpunit test, but should be used when multiple files are involved in the test, for example, or when we need to test the interaction between functionalities. You can learn more about the difference between unit and integration tests [here](https://www.browserstack.com/guide/unit-testing-vs-integration-testing).
In this suite, we must have the same structure of the MemberDash plugin and use the same rules of the wpunit suite to name the test files and methods.

We also have the `extra` directory, where we can put tests not related to a specific file, but to a global feature or functionality. Example: [extra/Autoloading.php](wpunit/../integration/extra/Autoloading.php) contains tests for the autoloading functionality.

### Snapshot suite (`snapshot` directory)

That suite contains snapshot tests. It's similar to the wpunit test, but should be used when we need to test the output of a template file, for example. When we run the snapshot tests, the module compares the current output with the snapshot file and fails if they are different. If the snapshot file doesn't exist, the module creates it. If the snapshot file exists, but the output is different, the module fails and shows the diff between the current output and the snapshot file.

Regarding the files hierarchy, we need to use the same rules of the wpunit suite, but changing the `templates` directory to `template-tests`, when the path includes it. Example: `themes/ld30/templates/exam/partials/exam_footer.php` becomes `tests/snapshot/themes/ld30/templates-tests/exam/partials/ExamFooterTest.php`.

Note: You can update the snapshot files using the `debug` option: `composer test:snapshot:debug`.

### Acceptance suite (`acceptance` directory)

That suite contains acceptance tests, running in a browser without JavaScript. Note that you **can't mock** anything in this suite neither use the `uopz` extension.

For these tests, we have the [WPDb](https://github.com/lucatume/wp-browser/blob/master/docs/modules/WPDb.md) module. So, you can change the database state and check database entries using it. It's is useful when we need to fill some data in the database to test a screen, for example. We're using `WPDb` in the context of an integration test (`WPTestCase` is the base class). Then, everything is happening in a transaction rolled back at the end of each test. See <https://wpbrowser.wptestkit.dev/modules/wploader#everything-happens-in-a-transaction>. It means that each test is isolated from the others.

In this suite, we might not have the same structure as the Memberdash plugin, as a test can cover multiple files, focusing on a specific user feature.

A test file should be named properly, describing the tested feature and following the rules below:

- Use the **PascalCase** naming convention
- Don't use `-` or `_`
- Don't use `class` prefix
- Add `Cest` suffix

Example: `StripeWebhookProcessingCest.php`.

Test methods should be named like `test_feature_x`. **All public methods** in the test class will be executed as tests.

### AcceptanceJS suite (`acceptancejs` directory)

That suite is exactly the same as the acceptance suite, but it runs with JavaScript enabled. Use this suite to test features that require JavaScript.

## How to write testable code

There are a few things you should do to make your code testable. Here are some tips:

1) Do not execute code when your file is imported. When you run some code in the **require** process, it's not possible to mock functions or classes properly.
2) When using a **protected or private** constructor, always provide a static method to instantiate it.

## References

- [Codeception](https://codeception.com/)
- [StellarWP Local Interactive Containers - slic](https://github.com/stellarwp/slic)
- [Mockery](http://docs.mockery.io/en/latest/)
- [FakerPHP documentation](https://fakerphp.github.io/)
