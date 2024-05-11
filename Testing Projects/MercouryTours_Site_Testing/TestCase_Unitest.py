__author__="Luke"

from selenium import webdriver
import unittest
import datetime #per l'orario



class Mytest_Case(unittest.TestCase):  #definisco una classe chiamata unit test

    @classmethod
    def setUp(self):
        self.driver = webdriver.Chrome("C:\\Users\\753920\\Downloads\\chromedriver_win32\\chromedriver.exe")# sto definendo il path
        print(" Enviroment Created")
        print(" Run Started at :" + str(datetime.datetime.now()))  # per dire l'orario
        print(".................................................................")
        self.driver.implicitly_wait(10)  #aspetto 10 secondi
        self.driver.set_page_load_timeout(20)
    @classmethod
    def tearDown(self):
        if(self.driver!=None):  #se esiste
            print(".................................................................")
            print(" Enviroment Destroyed")
            print(" Run Completed at :"+ str(datetime.datetime.now()))
            #self.driver.close() #close
            self.driver.quit() #esco

#if __name__=='__main__':
  #  unittest.main()  #definisco il main richiamo il metodo main che richiama








