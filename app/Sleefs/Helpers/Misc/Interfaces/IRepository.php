<?php
namespace Sleefs\Helpers\Misc\Interfaces;
/**
 * 
 * Esta interfaz define un contrato para la contrucción de objetos tipo repositorio
 * para acceso a datos en capas de persistencia heterogéneas. Basado en el patron
 * Repositorio (https://medium.com/@pererikbergman/repository-design-pattern-e28c0f3e4a30)
 * 
*/
interface IRepository{

	public function save(\stdClass $objData):mixed;
	public function search(\stdClass $objSearch):mixed;
	public function get($objId):mixed;
	public function delete ($objId):bool;


}