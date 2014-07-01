<?php
if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}
$output = "";

include_once('eFilter.class.php');
$eFltr = new eFilter($modx, $params);

$eFltr->modx->regClientCSS('assets/snippets/eFilter/html/css/eFilter.css');

//имя TV в котором содержится конфиг фильтров
$param_tv_name = $modx->db->getValue("SELECT name FROM " . $modx->getFullTableName('site_tmplvars') . " WHERE id = {$param_tv_id} LIMIT 0,1");


//получаем значение параметров для категории товара в виде массива
//если у ресурса не задано - смотрим родителя, если у родителя нет- смотрим дедушку
$eFltr->filter_param = $eFltr->getFilterParam ($param_tv_name);


//формируем основные массивы для описания наших фильтров
//на основе заданного в multiTV конфига
//$this->filter_tvs;
//$this->filter_names;
//$this->filter_cats;
//$this->filters;
//$this->list_tv_ids;

if (!empty($eFltr->filter_param['fieldValue'])) {//если не пусто, то формируем фильтры
    $eFltr->makeFilterArrays();
}


//получаем список id tv через запятую
//которые участвуют в фильтрации
//для последующих запросов
if (!empty($eFltr->filter_tvs)) {
    $eFltr->filter_tv_ids = implode(',', $eFltr->filter_tvs);
}


//получаем из конфигурации фильтра имена ТВ, которые используются в фильтрации
$eFltr->filter_tv_names = $eFltr->getTVNames ($eFltr->filter_tv_ids);


//получаем из конфигурации фильтра имена ТВ, которые используются в списке (для tvList DocLister)
if (!empty($eFltr->list_tv_ids)) {
    $eFltr->list_tv_names = $eFltr->getTVNames (implode(',', $eFltr->list_tv_ids));
    $eFltr->list_tv_captions = $eFltr->getTVNames (implode(',', $eFltr->list_tv_ids), 'caption');
}



//параметры DocLister по умолчанию
//он используется для поиска подходящих id ресурсов как без фильтров (категория, вложенность, опубликованность, удаленность и т.п.)
//так и с использованием фильтра
//на выходе получаем список id подходящих ресурсов через запятую
$DLparams = array('parents' => $eFltr->docid, 'tpl' => '@CODE [+id+],', 'depth' => '3', 'addWhereList' => 'c.template =' . $eFltr->params['product_template_id']);


//это список всех id товаров данной категории, дальше будем вычленять ненужные :)
$eFltr->content_ids_full = $eFltr->modx->runSnippet("DocLister", $DLparams);
$eFltr->content_ids_full = str_replace(' ', '', substr($eFltr->content_ids_full, 0, -1));


//получаем $eFltr->content_ids
//это пойдет в плейсхолдер (список documents через запятую
//как все подходящие к данному фильтру товары
//для подстановки в вызов DocLister и вывода списка отфильтрованных товаров на сайте
$eFltr->makeAllContentIDs($DLparams, $_GET);


//начинаем формировать фильтр
//проходимся по каждому фильтру и берем список всех товаров с учетом всех фильтров кроме текущего
//формируем по итогам массив $eFltr->curr_filter_values
//в котором каждому id тв фильтра соответствует список документов, которые подходят для всего фильтра за 
//исключением текущего
$eFltr->makeCurrFilterValuesContentIDs($DLparams, $_GET);

//берем все доступные значения для параметров до фильтрации
$eFltr->filter_values_full = $eFltr->getFilterValues ($eFltr->content_ids_full, $eFltr->filter_tv_ids);

//берем доступные  значения для параметров после фильтрации
//и формируем вывод фильтра с учетом количества для каждого из значений фильтра
//количество считаем исходя из сформированного списка подходящих документов из массива $eFltr->curr_filter_values
$eFltr->filter_values = $eFltr->getFilterFutureValues ($eFltr->curr_filter_values, $eFltr->filter_tv_ids);


//выводим блок фильтров (доступных после фильтрации)
//итоговый фильтр на вывод
$output = $eFltr->renderFilterBlock ($eFltr->filter_cats, $eFltr->filter_values_full, $eFltr->filter_values, $eFltr->filters, $eFltr->cfg);


//устанавливаем плейсхолдеры
$eFltr->setPlaceholders (
    array(
		//список документов для вывода (подставляем в DocLister, это происходит автоматом в сниппете getFilteredItems)
		"eFilter_ids" => $eFltr->content_ids,
		
		//форма вывода фильтра - вставить плейсхолдер в нужное место шаблона
		"eFilter_form" => $output,
		
		//перечень tv для вывода в список товаров
		//нужно для обозначения в списке tvList вызова DocLister  в сниппете getFilteredItems
		"eFilter_tv_list" => $eFltr->list_tv_names,
		
		//перечень tv для вывода в список товаров
		//нужно для вывода названий параметра рядом с его значением
		"eFilter_tv_names" => $eFltr->list_tv_captions
    )
);

