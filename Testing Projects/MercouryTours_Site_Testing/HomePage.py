from Locator import Locator
from selenium import webdriver
from selenium.webdriver import Chrome
from selenium import webdriver
from selenium.webdriver.common.by import By
import time  # per specificare il tempo di sleep
import unittest
import datetime  # per l'orario

__author__ = "Luke"

class HomePage(object):  # nel costruttore istanzio path+driver chrome
    def __init__(self, driver):
        #self.driver = driver
        #self.driver = webdriver.Chrome("C:\\Users\\753920\\Downloads\\chromedriver_win32\\chromedriver.exe")# sto definendo il path
        # path = "C:\\Users\\753920\Downloads\\chromedriver_win32\\chromedriver.exe"  # with double /
        # driver = Chrome(executable_path=path)
        # home page locators defining
        self.logo = driver.find_element(By.XPATH, Locator.logo)
        self.sign_on = driver.find_element(By.XPATH, Locator.sign_on)
        self.contact = driver.find_element(By.XPATH, Locator.contact)
        self.support = driver.find_element(By.XPATH, Locator.support)
        self.register = driver.find_element(By.XPATH, Locator.register)

    def getLogo(self):
        return self.logo

    def getSign_on(self):
        return self.sign_on

    def getContact(self):
        return self.contact

    def getSupport(self):
        return self.support

    def getRegister(self):
        return self.register

