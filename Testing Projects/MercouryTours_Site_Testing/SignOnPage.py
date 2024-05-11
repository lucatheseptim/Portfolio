__author__="Luke"

from Locator import Locator
from selenium.webdriver.common.by import By

class SignOn(object):
    def __init__(self,driver):
        self.driver=driver
#Home page locators defining
        self.userame= driver.find_element(By.XPATH, Locator.userName)
        self.password = driver.find_element(By.XPATH, Locator.password)
        self.login = driver.find_element(By.XPATH, Locator.signOn_login)
        self.welcomeText = driver.find_element(By.XPATH, Locator.signOn_txt)
        self.registrationLink = driver.find_element(By.XPATH, Locator.signOn_registerLink)
