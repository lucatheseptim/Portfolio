__author__="Luke"
from TestCaseBase.TestCase_Unitest import Mytest_Case #richiamo la classe
from HomePage import HomePage
import unittest
import HtmlTestRunner
from MercuryTours_Registration import MercuryTours_Registration


class MercuryTours_HomePage(Mytest_Case):   #gli passo come parametro la classe MytestCase


    def test_Home_Page(self): #definizione di un metodo della classe MercuryTours_HomePage

#Screenshot relative path

      # ss_path = "http://newtours.demoaut.com/mercurywelcome.php"

#Using the driver instance created in TestCase_Unitest
       driver = self.driver
       self.driver.get("http://newtours.demoaut.com") #prendo il sito dove lo carico
       self.driver.set_page_load_timeout(20)


#Creating object of the SS screenshot Utility

       expected_title = "Welcome: Mercoury Tours"

       try:  #verifico se c'è una eccezione
            if driver.title == expected_title:    #se il titolo è uguale a quello aspettato
                print ("Web Page loaded Successfully")
                self.assertEquals(driver.title,expected_title) #controllo con l'istruzione assertEquals
       except Exception as e:
            print(e+ "Web Page failed to load")

       m = HomePage(driver)#salvo nella variabile m il driver

       if m.getLogo().is_displayed():
            print(m.getLogo().get_attribute('alt')+" Logo Successfully Displayed")
       else:
            print(" Mercury Logo not Displayed")
       try:
            print("SignOn Link "+m.getSign_on().get_attribute('href'))  #il link che verrà visualizzato sulla pagina
            print("Contact Link "+m.getContact().get_attribute('href'))
            print("Register Link "+m.getRegister().get_attribute('href'))
            print("Support Link "+m.getSupport().get_attribute('href'))

       except Exception as e:
            print(e.__str__() + "Element not Present")



if __name__=='__main__':
    unittest.main(testRunner=HtmlTestRunner.HTMLTestRunner(output='C:/Users/753920/PycharmProjects/selium_project/Esercizio_MercouryTours/Reports_Test'))







