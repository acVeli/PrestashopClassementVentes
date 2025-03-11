<div class="panel">
    <h3><i class="icon-bar-chart"></i> {l s='Classement des ventes' mod='ac_salesranking'}</h3>
    
    {if isset($smarty.get.error) && $smarty.get.error == 'no_data'}
        <div class="alert alert-warning">
            {l s='Aucune donnée disponible pour la période sélectionnée.' mod='ac_salesranking'}
        </div>
    {/if}
    
    <div class="row">
        <div class="col-lg-12">
            {if isset($smarty.get.controller) && $smarty.get.controller == 'AdminStats'}
                {* Dans le contexte du hook Statistics *}
                <form class="form-horizontal" action="{$current_url|escape:'htmlall':'UTF-8'}&module_name=ac_salesranking&generate_csv=1" method="post">
            {else}
                {* Dans le contexte de la configuration du module *}
                <form class="form-horizontal" action="{$current_url|escape:'htmlall':'UTF-8'}&configure=ac_salesranking&generate_csv=1" method="post">
            {/if}
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Date de début' mod='ac_salesranking'}</label>
                    <div class="col-lg-4">
                        <input type="date" name="start_date" class="form-control" value="{$default_start_date|escape:'htmlall':'UTF-8'}" required />
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Date de fin' mod='ac_salesranking'}</label>
                    <div class="col-lg-4">
                        <input type="date" name="end_date" class="form-control" value="{$default_end_date|escape:'htmlall':'UTF-8'}" required />
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-lg-9 col-lg-offset-3">
                        <button type="submit" class="btn btn-primary">{l s='Générer CSV' mod='ac_salesranking'}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>