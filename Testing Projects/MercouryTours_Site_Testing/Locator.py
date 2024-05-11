__author__="Luke"


class Locator(object):
#Home page locator

    logo = "//img[@alt='Mercury Tours']"    #scendo per  vedere le immagini
    sign_on = "//a[contains(text(),'SIGN-ON')]"   #se nella pagina del sito c'è qualche parola di testo che contiene SIGN-ON, # a perchè è un link
    register = "//a[contains(text(),'REGISTER')]"
    support = "//a[contains(text(),'SUPPORT')]"
    contact = "//a[contains(text(),'CONTACT')]"

#Registration page locator
    regis_text="//*[contains(text(),'basic information')]"  #con l'asterisco cerco ovunque
    firstName="//input[@name='firstName']"
    lastName="//input[@name='lastName']"
    phone="//input[@name='lastName']"
    email="//input[@name='userName']"
   # country="//input[@name='country']"
    userName="//input[@name='email']"
    password="//input[@name='password']"
    confirmPassword="//input[@name='confirmPassword']"
    submit="//input[@name='register']"
#Post Registration Location

    #thank_you="//*[contains(text(),'Thank you for registering')]"
    #post_user="//*[contains(text(),'Your user name is ')]"

#sign on location User
    signOn_username="//input[@name='userName']"
    signOn_password="//input[@name='password']"
    signOn_login = "//input[@name='login']"
    signOn_txt="//*[contains(text(),'Enter your user')]"
    signOn_registerLink="//a[@href='mercuryregister.php']" #uso a perchè è un link con l'ancora


