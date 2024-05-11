__author__="Luke"

from selenium import webdriver
from Locator import Locator
from selenium.webdriver.common.by import By

class ConfirmationPage(object):
    def __init__(self,driver):
        self.driver = driver

#Defind the post Registration page object here
       # self.thankYou= driver.find_element(By.XPATH, Locator.thank_you)
        #self.userId= driver.find_element(By.XPATH, Locator.post_user)


