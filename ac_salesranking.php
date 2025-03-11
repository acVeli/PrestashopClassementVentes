<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Ac_Salesranking extends Module
{
    public function __construct()
    {
        $this->name = 'ac_salesranking';
        $this->tab = 'analytics_stats';
        $this->version = '1.0.0';
        $this->author = 'Anton COVU';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Classement des ventes');
        $this->description = $this->l('Génère un classement des ventes pour une période définie.');
    }

    public function install()
    {
        return parent::install() && 
               $this->registerHook('displayBackOfficeHeader') &&
               $this->registerHook('displayAdminStatsModules');
    }

    /**
     * Cette méthode est appelée pour toutes les pages d'administration
     * et permet de traiter la génération du CSV avant tout affichage
     */
    public function hookDisplayBackOfficeHeader()
    {
        // Vérifie si on génère un CSV depuis les stats
        if (Tools::getValue('module_name') == $this->name && Tools::getValue('generate_csv') == 1) {
            $this->processCsvExport();
        }
        // Vérifie également depuis la page de configuration
        elseif (Tools::getValue('controller') == 'AdminModules' && 
                Tools::getValue('configure') == $this->name && 
                Tools::getValue('generate_csv') == 1) {
            $this->processCsvExport();
        }
    }

    /**
     * Affiche le module dans la section Statistiques
     */
    public function hookDisplayAdminStatsModules()
    {
        $this->context->smarty->assign([
            'module_name' => $this->name,
            'current_url' => $_SERVER['REQUEST_URI'],
            'default_start_date' => date('Y-m-d', strtotime('-30 days')),
            'default_end_date' => date('Y-m-d')
        ]);

        return $this->display(__FILE__, 'views/templates/admin/sales_ranking_form.tpl');
    }

    /**
     * Affiche la page de configuration du module
     */
    public function getContent()
    {
        $this->context->smarty->assign([
            'module_name' => $this->name,
            'current_url' => $_SERVER['REQUEST_URI'],
            'default_start_date' => date('Y-m-d', strtotime('-30 days')),
            'default_end_date' => date('Y-m-d')
        ]);

        return $this->display(__FILE__, 'views/templates/admin/sales_ranking_form.tpl');
    }

    /**
     * Traite la demande d'exportation CSV
     */
    private function processCsvExport()
    {
        $startDate = Tools::getValue('start_date');
        $endDate = Tools::getValue('end_date');
        
        if (!Validate::isDate($startDate) || !Validate::isDate($endDate)) {
            return;
        }
        
        $salesData = $this->getSalesData($startDate, $endDate);
        
        if (empty($salesData)) {
            // En cas d'absence de données, rediriger avec un message
            if (Tools::getValue('module_name') == $this->name) {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminStats') . '&module=' . $this->name . '&error=no_data');
            } else {
                Tools::redirectAdmin($_SERVER['HTTP_REFERER'] . '&error=no_data');
            }
            return;
        }
        
        $this->generateCSV($salesData, $startDate, $endDate);
    }

    /**
     * Récupère les données de ventes
     */
    private function getSalesData($startDate, $endDate)
    {
        // Ajout d'une heure de fin à la date de fin pour inclure toute la journée
        $endDate = $endDate . ' 23:59:59';
        
        $sql = 'SELECT p.id_product, 
                       pl.name, 
                       SUM(od.product_quantity) as total_quantity, 
                       SUM(od.total_price_tax_incl) as total_sales,
                       ROUND(SUM(od.total_price_tax_incl)/SUM(od.product_quantity), 2) as average_price
                FROM '._DB_PREFIX_.'order_detail od
                JOIN '._DB_PREFIX_.'orders o ON od.id_order = o.id_order
                JOIN '._DB_PREFIX_.'product p ON od.product_id = p.id_product
                JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product 
                     AND pl.id_lang = '.(int)$this->context->language->id.'
                WHERE o.date_add BETWEEN "'.pSQL($startDate).'" AND "'.pSQL($endDate).'"
                AND o.valid = 1
                GROUP BY p.id_product
                ORDER BY total_quantity DESC';

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Génère et télécharge le fichier CSV
     */
    private function generateCSV($salesData, $startDate, $endDate)
    {
        // S'assurer qu'aucun contenu n'a déjà été envoyé
        if (!headers_sent()) {
            // Vider tout tampon de sortie existant
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            $filename = 'sales_ranking_'.$startDate.'_to_'.$endDate.'.csv';
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename='.$filename);
            header('Pragma: no-cache');
            header('Cache-Control: no-store, no-cache');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, [
                $this->l('ID Produit'), 
                $this->l('Nom'), 
                $this->l('Quantité Totale'), 
                $this->l('Ventes Totales (€)'),
                $this->l('Prix Moyen (€)')
            ]);
            
            foreach ($salesData as $row) {
                fputcsv($output, [
                    $row['id_product'],
                    $row['name'],
                    $row['total_quantity'],
                    number_format($row['total_sales'], 2, '.', ''),
                    $row['average_price']
                ]);
            }
            
            fclose($output);
            exit();
        } else {
            // Si des headers ont déjà été envoyés, on ne peut pas générer correctement le CSV
            die('Impossible de générer le CSV : des données ont déjà été envoyées au navigateur.');
        }
    }
}