__author__="Luke"

from unittest import TestLoader, TestSuite, TextTestRunner  # servono per lanciare i Test Case assieme
from MercuryTours_Registration import MercuryTours_Registration
from MercouryTours_SignOn import SignOn
from MercuryTours_HomePage import HomePage
import testtools as testtools

  #la libreria testtool mi serve per lanciare i 3 Test Case in Parallelo

if __name__ == '__main__':

    loader = TestLoader()
    suite = TestSuite((
        loader.loadTestsFromTestCase(HomePage),
        loader.loadTestsFromTestCase(SignOn),
        loader.loadTestsFromTestCase(MercuryTours_Registration)
    ))


 #run test sequencially using simple Test Runner
    runner = TextTestRunner(verbosity=2)
    runner.run(suite)


#run test parallel using concurrent_suite

    #concurrent_suite = testtools.ConcurrentStreamTestSuite(lambda: ((case, None) for case in suite))
    #concurrent_suite.run(testtools.StreamResult())