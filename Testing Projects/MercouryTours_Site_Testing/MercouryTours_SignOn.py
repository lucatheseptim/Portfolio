__author__="Luke"

import unittest
from SignOnPage import SignOn
from TestCaseBase.TestCase_Unitest import Mytest_Case #richiamo la classe
from HomePage import HomePage
from time import sleep
import HtmlTestRunner

class MercouryTours_SignOn(Mytest_Case):

    def test_SignOnPage(self):

        driver = self.driver
        self.driver.get("http://newtours.demoaut.com")  # prendo il sito dove lo carico
        self.driver.set_page_load_timeout(20)
        home = HomePage(driver)
        home.sign_on.click()
        sleep(4)

        #if(driver.title == "Welcome back to Mercury Tours!"): #chiedo al driver della pagina di controllare il titolo
        #    print("Sign-on Successfully!...")
       # else:
           # print("Sign-on failed to load!....")
        login = SignOn(driver) #salvo nella variabile login il driver
        try:
            print(login.welcomeText.get_attribute("innerText"))
            if login.registrationLink.text.find("registration"):
                print("registration link:"+ login.registrationLink.get_attribute("href"))
            print( "Provide invalid Username")
            login.userame.send_keys("invalid")
            print("Provide invalid Username")
            login.userame.send_keys("invalid")
            sleep(3)
            login.login.click()
            sleep(3)
            if driver.title == "Sign-On Mercoury Tours":
                print("invalid credenzial Provider")
        except Exception as e :
            print("Exception Occured "+e.__str__())


if __name__=='__main__':  #il main conterrà il test runner
    unittest.main(testRunner=HtmlTestRunner.HTMLTestRunner(output='C:/Users/753920/PycharmProjects/selium_project/Esercizio_MercouryTours/Reports_Test'))



