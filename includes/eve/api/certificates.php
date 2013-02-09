<?php
    class eveCertificateList {
        var $certificates = array();
        
        function eveCertificateList() {
        }
        
        function load($certs, $account) {
            foreach ($certs->row as $certificate) {
                $this->certificates[] = new eveKnownCertificate($account, $certificate);
            }
        }
    }

    class eveKnownCertificate {
        var $certificateID = 0;
        
        function eveKnownCertificate($acc, $certificate)  {
            $this->certificateID = (int)$certificate['certificateID'];
        }
    }

    class eveCertificateTree {
        var $categories = array();

        function eveCertificateTree() {
        }
        
        function load() {
            if (count($this->categories) == 0) {
                $data = new apiRequest('eve/CertificateTree.xml.aspx');
                if ((!$data->error) && ($data->data)) {
                    foreach ($data->data->result->rowset->row as $category) {
                        $this->categories[] = new eveCertificateCategory($category);
                    }
                }
            }
        }

        function getCertificate($certificateId) {
            for ($i = 0; $i < count($this->categories); $i++) {
                for ($j = 0; $j < count($this->categories[$i]->classes); $j++) {
                    for ($k = 0; $k < count($this->categories[$i]->classes[$j]->certificates); $k++) {
                        if ($this->categories[$i]->classes[$j]->certificates[$k]->certificateid == $certificateId)
                            return $this->categories[$i]->classes[$j]->certificates[$k];
                    }
                }
            }
            return false;
        }
    }

    class eveCertificateCategory {
        var $categoryID = 0;
        var $categoryName = "";
        var $classes = array();

        function eveCertificateCategory($category) {
            $this->categoryID = (int)$category['categoryID'];
            $this->categoryName = (string)$category['categoryName'];

            foreach ($category->rowset->row as $class)
                $this->classes[] = new eveCertificateClass($class, $this);
        }
    }

    class eveCertificateClass {
        var $classID = 0;
        var $className = "";
        var $certificates = array();
        var $caregory = null;

        function eveCertificateClass($class, $category) {
            $this->category = $category;
            $this->classID = (int)$class['classID'];
            $this->className = (string)$class['className'];

            foreach ($class->rowset->row as $cert) {
                $this->certificates[] = eveDB::getInstance()->eveCertificate((int)$cert['certificateID']);
                $this->certificates[count($this->certificates)-1]->cclass = $this;
            }
        }
    }

?>