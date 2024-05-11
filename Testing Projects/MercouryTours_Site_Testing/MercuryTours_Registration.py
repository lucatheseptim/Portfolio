__author__="Luke"

from TestCaseBase.TestCase_Unitest import Mytest_Case
from HomePage import HomePage
from RegistrationPage import Register
from ConfirmationPage import ConfirmationPage
from time import sleep
import HtmlTestRunner
import unittest


class MercuryTours_Registration(Mytest_Case): #gli passo come parametro la classe MytestCase che contiene i Test

    def test_RegistrationFlow(self):
        driver = self.driver
        self.driver.get("http://newtours.demoaut.com/mercuryregister.php")
        self.driver.set_page_load_timeout(20)

    #calling Home page object to click on Register link

        home = HomePage(driver)
        if home.getRegister().is_displayed():   #se il link della Registrazione è cliccato
            print("register link displaying...")
            home.getRegister().click()
            sleep(1)
    #calling Registration page object to proced to registration flow
        reg = Register(driver)
        if reg.getRegis_txt().is_displayed():
            print(reg.getRegis_txt().text)   #lo stampo
        else:
            print(" Registration Page not Loaded")
        try:
            reg.setFirstName("Luca")
            sleep(3)
            reg.setlastName("Airoldi")
            sleep(3)
            reg.phone("3480150047")
            sleep(3)
            reg.email("luke93@gmail.com")
            sleep(3)
            #reg.country("Italy")
            reg.password("Pippo123")
            sleep(3)
            reg.confirmPassword("Pippo123")
            sleep(5)
            reg.submit()
            sleep(5)
        except Exception as e:
            print (" Exception Occurred:" +e.__str__())

#calling Post Registration Check
        #post = ConfirmationPage(driver)
        #print(post.thankYou.text)
        #if(post.userId.text).find("luke93@gmail.com"): # lo cerco se è uguale
            #print("Registration failed")
        #else:
            #print("Registartion Successful......")


if __name__=='__main__':
    unittest.main(testRunner=HtmlTestRunner.HTMLTestRunner(output='C:/Users/753920/PycharmProjects/selium_project/Esercizio_MercouryTours/Reports_Test'))