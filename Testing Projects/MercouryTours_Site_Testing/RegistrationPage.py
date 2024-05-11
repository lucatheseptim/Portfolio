__author__="Luke"

from Locator import Locator
from selenium.webdriver.common.by import By
from selenium.webdriver.support.select import Select  #la uso quando c'è una Drop-down menu o combo Box

class Register(object):
    def __init__(self,driver):
        self.driver= driver

#registration page locator defining
        self.regis_txt = driver.find_element(By.XPATH, Locator.regis_text)
        self.firstName = driver.find_element(By.XPATH, Locator.firstName)
        self.lastName = driver.find_element(By.XPATH, Locator.lastName)
        self.phone = driver.find_element(By.XPATH, Locator.phone)
        self.email = driver.find_element(By.XPATH, Locator.email)
        #self.country = driver.find_element(By.XPATH, Locator.country)
        self.password = driver.find_element(By.XPATH, Locator.password)
        self.confirmPassword = driver.find_element(By.XPATH, Locator.confirmPassword)
        self.submit = driver.find_element(By.XPATH, Locator.submit)

#Return Web Element
    def getRegis_txt(self):
        return self.regis_txt

#write element in the Web Page

    def setFirstName(self,Name):
        self.firstName.clear()
        self.firstName.send_keys(Name) # invio sulla web Page del sito il nome

    def setlastName(self,Name):
        self.lastName().clear()
        self.lastName().send_keys(Name) # invio sulla web Page del sito il nome

    def setPhone(self,phone):
        self.phone.clear()
        self.phone.send_keys(phone) # invio sulla web Page del sito il nome

    def setCountry(self,country):
        self.firstName.clear()
        self.firstName.send_keys(country) # invio sulla web Page del sito il nome

    def setPassword(self,password):
        self.firstName.clear()
        self.firstName.send_keys(password) # invio sulla web Page del sito il nome

    def setConfirmPassword(self,confirmPassword):
        self.firstName.clear()
        self.firstName.send_keys(confirmPassword) # invio sulla web Page del sito il nome

    def setSubmit(self, submit):
            self.firstName.clear()
            self.firstName.send_keys(submit)  # invio sulla web Page del sito il nome





