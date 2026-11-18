================================================================
 Testing Projects
================================================================

Automated test suites and testing exercises.

Contents
--------

MercouryTours_Site_Testing/
    Selenium + Python automated UI test suite for the Mercury Tours
    demo web application. Built in 2018, Python 3.7 era.


================================================================
 MercouryTours_Site_Testing
================================================================

Purpose
-------

A practice project on test automation: exercising a real web
application through the browser, using the Page Object Model to keep
test code readable and maintainable.

The suite covers three flows of the Mercury Tours demo site:

  - home page: presence and correctness of the main navigation links
  - sign-on: authentication form interaction
  - registration: filling and submitting the account creation form

Technology stack
----------------

  Language            Python 3.7
  Browser automation  Selenium WebDriver (Chrome)
  Test framework      unittest
  Test runner         unittest TestLoader / TestSuite / TextTestRunner,
                      with testtools for parallel execution
  Reporting           HTML reports generated per run
  IDE                 PyCharm (project files in .idea/)

Project structure
-----------------

  Locator.py                    All XPath locators, one class, grouped
                                by page. Keeping them in one file means a
                                UI change is fixed in a single place.

  HomePage.py                   Page Object: home page
  RegistrationPage.py           Page Object: registration form
  SignOnPage.py                 Page Object: sign-on form
  ConfirmationPage.py           Page Object: post-submission page

  MercuryTours_HomePage.py      Test case: home page
  MercuryTours_Registration.py  Test case: registration flow
  MercouryTours_SignOn.py       Test case: sign-on flow

  TestCaseBase/
    TestCase_Unitest.py         Base test case: WebDriver setup and
                                teardown, implicit wait, page load
                                timeout, timestamped logging

  TestSuit/
    TestRunner.py               Aggregates the three test cases and runs
                                the suite

  TestRunnerCase.py             Standalone runner for a single case

  Reports_Test/                 HTML report of each execution, named by
                                test and timestamp (November 2018)

Design notes
------------

The suite follows the Page Object Model: each page of the application
is represented by a class that exposes its elements, and the test cases
interact only with those classes. Locators are never written inline in
the tests.

The base test case centralises WebDriver lifecycle: a fresh Chrome
instance per test class, a 10 second implicit wait and a 20 second page
load timeout, so that individual tests do not repeat configuration.

How to run
----------

  1. Install dependencies:

        pip install selenium testtools

  2. ChromeDriver must match the installed Chrome version.

     Note: the driver path is hardcoded in
     TestCaseBase/TestCase_Unitest.py and points to the original
     development machine:

        C:\Users\753920\Downloads\chromedriver_win32\chromedriver.exe

     Change that path, or move the driver to a directory on PATH.

  3. Run the whole suite:

        python TestSuit/TestRunner.py

  4. Or run a single case:

        python TestRunnerCase.py

Test reports
------------

Every execution writes an HTML report into Reports_Test/, with the test
name and the run timestamp in the filename. Four reports from November
2018 are included as evidence of the executions.

Known limitations
-----------------

  - The suite targets the public Mercury Tours demo site, a sample
    application that is no longer reliably reachable. The code is kept
    as a record of the approach; running it today may require pointing
    the locators at an equivalent demo application.

  - The ChromeDriver path is hardcoded and specific to one machine.

  - __pycache__/ and .idea/ are committed as they were in the original
    working copy; in a maintained project they would be ignored.

  - The tests are Chrome-only and assume Windows-style paths.

Author
------

Luca - 2018
