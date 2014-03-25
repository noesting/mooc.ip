from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
import unittest, time, re
import mysuite

class HtmlBlock(unittest.TestCase):
    def setUp(self):
        self.driver = mysuite.getOrCreateWebdriver()
        self.driver.implicitly_wait(30)
        self.base_url = "http://vm036.rz.uos.de/studip/mooc/"
        self.verificationErrors = []
        self.accept_next_alert = True
    
    def test_html_block(self):
        driver = self.driver
        driver.get("http://vm036.rz.uos.de/studip/mooc/plugins.php/mooc/courseware?cid=2358add583efc4c04d209ff257b9d9c4&selected=10")
        driver.find_element_by_css_selector("button.author").click()
        driver.find_element_by_link_text("HtmlBlock").click()
        driver.find_element_by_css_selector("div.controls > button.author").click()
        driver.find_element_by_name("content").clear()
        driver.find_element_by_name("content").send_keys("Selenium Test")
        driver.find_element_by_css_selector("button.button").click()
        try: self.assertEqual("Selenium Test", driver.find_element_by_css_selector("div.content").text)
        except AssertionError as e: self.verificationErrors.append(str(e))
        driver.find_element_by_css_selector("button.trash").click()
    
    def is_element_present(self, how, what):
        try: self.driver.find_element(by=how, value=what)
        except NoSuchElementException, e: return False
        return True
    
    def is_alert_present(self):
        try: self.driver.switch_to_alert()
        except NoAlertPresentException, e: return False
        return True
    
    def close_alert_and_get_its_text(self):
        try:
            alert = self.driver.switch_to_alert()
            alert_text = alert.text
            if self.accept_next_alert:
                alert.accept()
            else:
                alert.dismiss()
            return alert_text
        finally: self.accept_next_alert = True
    
    def tearDown(self):
        #self.driver.quit()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":
    unittest.main()