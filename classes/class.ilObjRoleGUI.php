<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/


/**
* Class ilObjRoleGUI
*
* @author Stefan Meyer <smeyer@databay.de> 
* $Id$Id: class.ilObjRoleGUI.php,v 1.89.2.1 2005/02/14 12:21:03 smeyer Exp $
* 
* @extends ilObjectGUI
* @package ilias-core
*/

require_once "class.ilObjectGUI.php";

class ilObjRoleGUI extends ilObjectGUI
{
	/**
	* ILIAS3 object type abbreviation
	* @var		string
	* @access	public
	*/
	var $type;

	/**
	* rolefolder ref_id where role is assigned to
	* @var		string
	* @access	public
	*/
	var $rolf_ref_id;


	var $ctrl;
 
	/**
	* Constructor
	* @access public
	*/
	function ilObjRoleGUI($a_data,$a_id,$a_call_by_reference)
	{
		global $ilCtrl;

		$this->type = "role";
		$this->ilObjectGUI($a_data,$a_id,$a_call_by_reference);
		$this->rolf_ref_id =& $this->ref_id;

		$this->ctrl =& $ilCtrl;
		$this->ctrl->saveParameter($this,'obj_id');
	}

	function &executeCommand()
	{
		global $rbacsystem;

		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();
		switch($next_class)
		{
			default:
				if(!$cmd)
				{
					$cmd = "view";
				}
				$cmd .= "Object";
				$this->$cmd();
					
				break;
		}
		return true;
	}

	/**
	* display role create form
	*/
	function createObject()
	{
		global $rbacsystem;
		
		if (!$rbacsystem->checkAccess('create_role', $this->rolf_ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}

		$this->getTemplateFile("edit","role");

		if ($this->rolf_ref_id == ROLE_FOLDER_ID)
		{
			$this->tpl->setCurrentBlock("allow_register");
			$allow_register = ($_SESSION["error_post_vars"]["Fobject"]["allow_register"]) ? "checked=\"checked\"" : "";
			$this->tpl->setVariable("TXT_ALLOW_REGISTER",$this->lng->txt("allow_register"));
			$this->tpl->setVariable("ALLOW_REGISTER",$allow_register);
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock("assign_users");
			$assign_users = $_SESSION["error_post_vars"]["Fobject"]["assign_users"] ? "checked=\"checked\"" : "";
			$this->tpl->setVariable("TXT_ASSIGN_USERS",$this->lng->txt("allow_assign_users"));
			$this->tpl->setVariable("ASSIGN_USERS",$assign_users);
			$this->tpl->parseCurrentBlock();
		}

		// fill in saved values in case of error
		$this->tpl->setVariable("TITLE",ilUtil::prepareFormOutput($_SESSION["error_post_vars"]["Fobject"]["title"]),true);
		$this->tpl->setVariable("DESC",ilUtil::stripSlashes($_SESSION["error_post_vars"]["Fobject"]["desc"]));

		$this->tpl->setVariable("TXT_TITLE",$this->lng->txt("title"));
		$this->tpl->setVariable("TXT_DESC",$this->lng->txt("desc"));
		$this->tpl->setVariable("FORMACTION", $this->getFormAction("save","adm_object.php?cmd=gateway&ref_id=".$this->rolf_ref_id."&new_type=".$this->type));
		$this->tpl->setVariable("TXT_HEADER", $this->lng->txt($this->type."_new"));
		$this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
		$this->tpl->setVariable("TXT_SUBMIT", $this->lng->txt($this->type."_add"));
		$this->tpl->setVariable("CMD_SUBMIT", "save");
		$this->tpl->setVariable("TARGET", $this->getTargetFrame("save"));
		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
	}

	/**
	* save a new role object
	*
	* @access	public
	*/
	function saveObject()
	{
		global $rbacsystem, $rbacadmin, $rbacreview;

		// check for create role permission
		if (!$rbacsystem->checkAccess("create_role",$this->rolf_ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_create_role"),$this->ilias->error_obj->MESSAGE);
		}

		// check required fields
		if (empty($_POST["Fobject"]["title"]))
		{
			$this->ilias->raiseError($this->lng->txt("fill_out_all_required_fields"),$this->ilias->error_obj->MESSAGE);
		}

		// check if role title is unique
		if ($rbacreview->roleExists($_POST["Fobject"]["title"]))
		{
			$this->ilias->raiseError($this->lng->txt("msg_role_exists1")." '".ilUtil::stripSlashes($_POST["Fobject"]["title"])."' ".
									 $this->lng->txt("msg_role_exists2"),$this->ilias->error_obj->MESSAGE);
		}
		
		// check if role title has il_ prefix
		if (substr($_POST["Fobject"]["title"],0,3) == "il_")
		{
			$this->ilias->raiseError($this->lng->txt("msg_role_reserved_prefix"),$this->ilias->error_obj->MESSAGE);
		}		

		// save
		include_once("./classes/class.ilObjRole.php");
		$roleObj = new ilObjRole();
		//$roleObj->assignData($_POST["Fobject"]);
		$roleObj->setTitle(ilUtil::stripSlashes($_POST["Fobject"]["title"]));
		$roleObj->setDescription(ilUtil::stripSlashes($_POST["Fobject"]["desc"]));
		$roleObj->setAllowRegister($_POST["Fobject"]["allow_register"]);
		$roleObj->toggleAssignUsersStatus($_POST["Fobject"]["assign_users"]);
		$roleObj->create();
		$rbacadmin->assignRoleToFolder($roleObj->getId(), $this->rolf_ref_id,'y');
		
		sendInfo($this->lng->txt("role_added"),true);

		ilUtil::redirect("adm_object.php?ref_id=".$this->rolf_ref_id);
	}

	/**
	* display permission settings template
	*
	* @access	public
	*/
	function permObject()
	{
		global $rbacadmin, $rbacreview, $rbacsystem,$objDefinition;


		#$to_filter = $objDefinition->getSubobjectsToFilter();

		if (!$rbacsystem->checkAccess('visible,write',$this->rolf_ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_perm"),$this->ilias->error_obj->MESSAGE);
			exit();
		}

		// build array with all rbac object types
		$q = "SELECT ta.typ_id,obj.title,ops.ops_id,ops.operation FROM rbac_ta AS ta ".
			 "LEFT JOIN object_data AS obj ON obj.obj_id=ta.typ_id ".
			 "LEFT JOIN rbac_operations AS ops ON ops.ops_id=ta.ops_id";
		$r = $this->ilias->db->query($q);

		while ($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			// FILTER SUBOJECTS OF adm OBJECT
			#if(in_array($row->title,$to_filter))
			#{
			#	continue;
			#}
			$rbac_objects[$row->typ_id] = array("obj_id"	=> $row->typ_id,
											    "type"		=> $row->title
												);

			$rbac_operations[$row->typ_id][$row->ops_id] = array(
									   							"ops_id"	=> $row->ops_id,
									  							"title"		=> $row->operation,
																"name"		=> $this->lng->txt($row->title."_".$row->operation)
															   );
		}
			
		foreach ($rbac_objects as $key => $obj_data)
		{
			$rbac_objects[$key]["name"] = $this->lng->txt("obj_".$obj_data["type"]);
			$rbac_objects[$key]["ops"] = $rbac_operations[$key];
		}


		// for local roles display only the permissions settings for allowed subobjects
		if ($this->rolf_ref_id != ROLE_FOLDER_ID)
		{
			// first get object in question (parent of role folder object)
			$parent_data = $this->tree->getParentNodeData($this->rolf_ref_id);
			// get allowed subobject of object
			$subobj_data = $this->objDefinition->getSubObjects($parent_data["type"]);
			
			// remove not allowed object types from array but keep the type definition of object itself
			foreach ($rbac_objects as $key => $obj_data)
			{
				if (!$subobj_data[$obj_data["type"]] and $parent_data["type"] != $obj_data["type"])
				{
					unset($rbac_objects[$key]);
				}
			}
		} // end if local roles
		
		// now sort computed result
		sort($rbac_objects);
			
		foreach ($rbac_objects as $key => $obj_data)
		{
			sort($rbac_objects[$key]["ops"]);
		}
		
		// sort by (translated) name of object type
		$rbac_objects = ilUtil::sortArray($rbac_objects,"name","asc");

		// BEGIN CHECK_PERM
		$global_roles_all = $rbacreview->getGlobalRoles();
		$global_roles_user = array_intersect($_SESSION["RoleId"],$global_roles_all);
		
		// is this role a global role?
		if (in_array($this->object->getId(),$global_roles_all))
		{
			$global_role = true;
		}
		else
		{
			$global_role = false;
		}

		foreach ($rbac_objects as $key => $obj_data)
		{
			$allowed_ops_on_type = array();

			foreach ($global_roles_user as $role_id)
			{
				$allowed_ops_on_type = array_merge($allowed_ops_on_type,$rbacreview->getOperationsOfRole($role_id,$obj_data["type"]));
			}
				
			$allowed_ops_on_type = array_unique($allowed_ops_on_type);
				
			$arr_selected = $rbacreview->getOperationsOfRole($this->object->getId(), $obj_data["type"], $this->rolf_ref_id);
			$arr_checked = array_intersect($arr_selected,array_keys($rbac_operations[$obj_data["obj_id"]]));

			foreach ($rbac_operations[$obj_data["obj_id"]] as $operation)
			{
				// check all boxes for system role
				if ($this->object->getId() == SYSTEM_ROLE_ID)
				{
					$checked = true;
					$disabled = true;
				}
				else
				{
					$checked = in_array($operation["ops_id"],$arr_checked);

					// for global roles only allow to set those permission the current user is granted himself except SYSTEM_ROLE_ID !!
					if (!in_array(SYSTEM_ROLE_ID,$_SESSION["RoleId"]) and $global_role == true and 
						!in_array($operation["ops_id"],$allowed_ops_on_type))
					{
						$disabled = true;
					}
					else
					{
						$disabled = false;
					}
				}

				// Es wird eine 2-dim Post Variable �bergeben: perm[rol_id][ops_id]
				$box = ilUtil::formCheckBox($checked,"template_perm[".$obj_data["type"]."][]",$operation["ops_id"],$disabled);
				$output["perm"][$obj_data["obj_id"]][$operation["ops_id"]] = $box;
			}
		}
		// END CHECK_PERM

		$output["col_anz"] = count($rbac_objects);
		$output["txt_save"] = $this->lng->txt("save");
		$output["check_bottom"] = ilUtil::formCheckBox(0,"recursive",1);
		$output["message_table"] = $this->lng->txt("change_existing_objects");


/************************************/
/*		adopt permissions form		*/
/************************************/

		$output["message_middle"] = $this->lng->txt("adopt_perm_from_template");

		// send message for system role
		if ($this->object->getId() == SYSTEM_ROLE_ID)
		{
			$output["adopt"] = array();
			$output["sysrole_msg"] = $this->lng->txt("msg_sysrole_not_editable");
		}
		else
		{
			// BEGIN ADOPT_PERMISSIONS
			$parent_role_ids = $rbacreview->getParentRoleIds($this->rolf_ref_id,true);

			// sort output for correct color changing
			ksort($parent_role_ids);

			foreach ($parent_role_ids as $key => $par)
			{
				if ($par["obj_id"] != SYSTEM_ROLE_ID)
				{
					$radio = ilUtil::formRadioButton(0,"adopt",$par["obj_id"]);
					$output["adopt"][$key]["css_row_adopt"] = ilUtil::switchColor($key, "tblrow1", "tblrow2");
					$output["adopt"][$key]["check_adopt"] = $radio;
					$output["adopt"][$key]["type"] = ($par["type"] == 'role' ? 'Role' : 'Template');
					$output["adopt"][$key]["role_name"] = $par["title"];
				}
			}

			$output["formaction_adopt"] = "adm_object.php?cmd=adoptPermSave&ref_id=".$this->rolf_ref_id."&obj_id=".$this->object->getId();
			// END ADOPT_PERMISSIONS
		}

		$output["formaction"] = "adm_object.php?cmd=permSave&ref_id=".$this->rolf_ref_id."&obj_id=".$this->object->getId();

		$this->data = $output;


/************************************/
/*			generate output			*/
/************************************/

		$this->tpl->addBlockFile("CONTENT", "content", "tpl.adm_content.html");
		$this->tpl->addBlockFile("LOCATOR", "locator", "tpl.locator.html");
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.adm_perm_role.html");

		foreach ($rbac_objects as $obj_data)
		{
			// BEGIN object_operations
			$this->tpl->setCurrentBlock("object_operations");

			foreach ($obj_data["ops"] as $operation)
			{
				$css_row = ilUtil::switchColor($key, "tblrow1", "tblrow2");
				$this->tpl->setVariable("CSS_ROW",$css_row);
				$this->tpl->setVariable("PERMISSION",$operation["name"]);
				if (substr($operation["title"], 0, 7) == "create_")
				{
					if ($this->objDefinition->getDevMode(substr($operation["title"], 7, strlen($operation["title"]) -7)))
					{
						$this->tpl->setVariable("TXT_NOT_IMPL", "(".$this->lng->txt("not_implemented_yet").")");
					}
				}
				$this->tpl->setVariable("CHECK_PERMISSION",$this->data["perm"][$obj_data["obj_id"]][$operation["ops_id"]]);
				$this->tpl->parseCurrentBlock();
			} // END object_operations

			// BEGIN object_type
			$this->tpl->setCurrentBlock("object_type");
			$this->tpl->setVariable("TXT_OBJ_TYPE",$obj_data["name"]);
			if ($this->objDefinition->getDevMode($obj_data["type"]))
			{
				$this->tpl->setVariable("TXT_NOT_IMPL", "(".$this->lng->txt("not_implemented_yet").")");
			}
			$this->tpl->parseCurrentBlock();
			// END object_type
		}

		// don't display adopt permissions form for system role
		if ($this->object->getId() != SYSTEM_ROLE_ID)
		{
			// BEGIN ADOPT PERMISSIONS
			foreach ($this->data["adopt"] as $key => $value)
			{
				$this->tpl->setCurrentBlock("ADOPT_PERM_ROW");
				$this->tpl->setVariable("CSS_ROW_ADOPT",$value["css_row_adopt"]);
				$this->tpl->setVariable("CHECK_ADOPT",$value["check_adopt"]);
				$this->tpl->setVariable("TYPE",$value["type"]);
				$this->tpl->setVariable("ROLE_NAME",$value["role_name"]);
				$this->tpl->parseCurrentBlock();
			}
			
			$this->tpl->setCurrentBlock("ADOPT_PERM_FORM");
			$this->tpl->setVariable("MESSAGE_MIDDLE",$this->data["message_middle"]);
			$this->tpl->setVariable("FORMACTION_ADOPT",$this->data["formaction_adopt"]);
			$this->tpl->parseCurrentBlock();
			// END ADOPT PERMISSIONS
		
			$this->tpl->setCurrentBlock("tblfooter_recursive");
			$this->tpl->setVariable("COL_ANZ",3);
			$this->tpl->setVariable("CHECK_BOTTOM",$this->data["check_bottom"]);
			$this->tpl->setVariable("MESSAGE_TABLE",$this->data["message_table"]);
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock("tblfooter_standard");
			$this->tpl->setVariable("COL_ANZ_PLUS",4);
			$this->tpl->setVariable("TXT_SAVE",$this->data["txt_save"]);
			$this->tpl->parseCurrentBlock();
		}
		else
		{
			// display form buttons not for system role
			$this->tpl->setCurrentBlock("tblfooter_sysrole");
			$this->tpl->setVariable("COL_ANZ_SYS",3);
			$this->tpl->parseCurrentBlock();

			// display sysrole_msg
			$this->tpl->setCurrentBlock("sysrole_msg");
			$this->tpl->setVariable("TXT_SYSROLE_MSG",$this->data["sysrole_msg"]);
			$this->tpl->parseCurrentBlock();
		}
		
		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("TBL_TITLE_IMG",ilUtil::getImagePath("icon_".$this->object->getType()."_b.gif"));
		$this->tpl->setVariable("TBL_TITLE_IMG_ALT",$this->lng->txt($this->object->getType()));
		$this->tpl->setVariable("TBL_HELP_IMG",ilUtil::getImagePath("icon_help.gif"));
		$this->tpl->setVariable("TBL_HELP_LINK","tbl_help.php");
		$this->tpl->setVariable("TBL_HELP_IMG_ALT",$this->lng->txt("help"));
		$this->tpl->setVariable("TBL_TITLE",$this->object->getTitle());
			
		$this->tpl->setVariable("TXT_PERMISSION",$this->data["txt_permission"]);
		$this->tpl->setVariable("FORMACTION",$this->data["formaction"]);
		$this->tpl->parseCurrentBlock();
	}

	/**
	* save permissions
	* 
	* @access	public
	*/
	function permSaveObject()
	{
		global $rbacsystem, $rbacadmin, $rbacreview,$objDefinition;

		// SET TEMPLATE PERMISSIONS
		if (!$rbacsystem->checkAccess('write', $this->rolf_ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_perm"),$this->ilias->error_obj->MESSAGE);
		}

		#$to_filter = $objDefinition->getSubobjectsToFilter();

		// first safe permissions that were disabled in HTML form due to missing lack of permissions of user who changed it
		// TODO: move this following if-code into an extra function. this part is also used in $this->permObject !!
		if (!in_array(SYSTEM_ROLE_ID,$_SESSION["RoleId"]))
		{
			// build array with all rbac object types
			$q = "SELECT ta.typ_id,obj.title,ops.ops_id,ops.operation FROM rbac_ta AS ta ".
				 "LEFT JOIN object_data AS obj ON obj.obj_id=ta.typ_id ".
				 "LEFT JOIN rbac_operations AS ops ON ops.ops_id=ta.ops_id";
			$r = $this->ilias->db->query($q);
	
			while ($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
			{
				// FILTER SUBOJECTS OF adm OBJECT
				#if(in_array($row->title,$to_filter))
				#{
				#	continue;
				#}

				$rbac_objects[$row->typ_id] = array("obj_id"	=> $row->typ_id,
												    "type"		=> $row->title
													);
	
				$rbac_operations[$row->typ_id][$row->ops_id] = array(
										   							"ops_id"	=> $row->ops_id,
										  							"title"		=> $row->operation,
																	"name"		=> $this->lng->txt($row->title."_".$row->operation)
																   );
			}
				
			foreach ($rbac_objects as $key => $obj_data)
			{
				$rbac_objects[$key]["name"] = $this->lng->txt("obj_".$obj_data["type"]);
				$rbac_objects[$key]["ops"] = $rbac_operations[$key];
			}
	
			$global_roles_all = $rbacreview->getGlobalRoles();
			$global_roles_user = array_intersect($_SESSION["RoleId"],$global_roles_all);
			
			foreach ($rbac_objects as $key => $obj_data)
			{
				$allowed_ops_on_type = array();
	
				foreach ($global_roles_user as $role_id)
				{
					$allowed_ops_on_type = array_merge($allowed_ops_on_type,$rbacreview->getOperationsOfRole($role_id,$obj_data["type"]));
				}
					
				$allowed_ops_on_type = array_unique($allowed_ops_on_type);
					
				$arr_previous = $rbacreview->getOperationsOfRole($this->object->getId(), $obj_data["type"], $this->rolf_ref_id);
				$arr_missing = array_diff($arr_previous,$allowed_ops_on_type);
				
				$_POST["template_perm"][$obj_data["type"]] = array_merge($_POST["template_perm"][$obj_data["type"]],$arr_missing);
				
				// remove empty types
				if (empty($_POST["template_perm"][$obj_data["type"]]))
				{
					unset($_POST["template_perm"][$obj_data["type"]]);
				}
			}
		} // END TODO: move!!!

		// delete all template entries
		$rbacadmin->deleteRolePermission($this->object->getId(), $this->rolf_ref_id);

		if (empty($_POST["template_perm"]))
		{
			$_POST["template_perm"] = array();
		}

		foreach ($_POST["template_perm"] as $key => $ops_array)
		{
			// sets new template permissions
			$rbacadmin->setRolePermission($this->object->getId(), $key, $ops_array, $this->rolf_ref_id);
		}

		// update object data entry (to update last modification date)
		$this->object->update();

		// CHANGE ALL EXISTING OBJECT UNDER PARENT NODE OF ROLE FOLDER
		// BUT DON'T CHANGE PERMISSIONS OF SUBTREE OBJECTS IF INHERITANCE WAS STOPPED
		if ($_POST["recursive"])
		{
			// IF ROLE IS A GLOBAL ROLE START AT ROOT
			if ($this->rolf_ref_id == ROLE_FOLDER_ID)
			{
				$node_id = ROOT_FOLDER_ID;
			}
			else
			{
				$node_id = $this->tree->getParentId($this->rolf_ref_id);
			}

			// GET ALL SUBNODES
			$node_data = $this->tree->getNodeData($node_id);
			$subtree_nodes = $this->tree->getSubTree($node_data);

			// GET ALL OBJECTS THAT CONTAIN A ROLE FOLDER
			$all_parent_obj_of_rolf = $rbacreview->getObjectsWithStopedInheritance($this->object->getId());

			// DELETE ACTUAL ROLE FOLDER FROM ARRAY
			if ($this->rolf_ref_id == ROLE_FOLDER_ID)
			{
				$key = array_keys($all_parent_obj_of_rolf,SYSTEM_FOLDER_ID);
			}
			else
			{
				$key = array_keys($all_parent_obj_of_rolf,$node_id);
			}

			unset($all_parent_obj_of_rolf[$key[0]]);

			$check = false;

			foreach ($subtree_nodes as $node)
			{
				if (!$check)
				{
					if (in_array($node["child"],$all_parent_obj_of_rolf))
					{
						$lft = $node["lft"];
						$rgt = $node["rgt"];
						$check = true;
						continue;
					}

					$valid_nodes[] = $node;
				}
				else
				{
					if (($node["lft"] > $lft) && ($node["rgt"] < $rgt))
					{
						continue;
					}
					else
					{
						$check = false;
						$valid_nodes[] = $node;
					}
				}
			}

			// prepare arrays for permission settings below
			foreach ($valid_nodes as $key => $node)
			{
				#if(!in_array($node["type"],$to_filter))
				{
					$node_ids[] = $node["child"];
					$valid_nodes[$key]["perms"] = $_POST["template_perm"][$node["type"]];
				}
			}
			
			// FIRST REVOKE PERMISSIONS FROM ALL VALID OBJECTS
			$rbacadmin->revokePermissionList($node_ids,$this->object->getId());

			// NOW SET ALL PERMISSIONS
			foreach ($valid_nodes as $node)
			{
				if (is_array($node["perms"]))
				{
					$rbacadmin->grantPermission($this->object->getId(),$node["perms"],$node["child"]);
				}
			}
		}// END IF RECURSIVE
		


		sendinfo($this->lng->txt("saved_successfully"),true);

		ilUtil::redirect("adm_object.php?ref_id=".$this->rolf_ref_id."&obj_id=".$this->object->getId()."&cmd=perm");
	}


	/**
	* copy permissions from role
	* 
	* @access	public
	*/
	function adoptPermSaveObject()
	{
		global $rbacadmin, $rbacsystem, $rbacreview;

		if (!$rbacsystem->checkAccess('write',$this->rolf_ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_perm"),$this->ilias->error_obj->MESSAGE);
		}
		elseif ($this->object->getId() == $_POST["adopt"])
		{
			sendInfo($this->lng->txt("msg_perm_adopted_from_itself"),true);
		}
		else
		{
			$rbacadmin->deleteRolePermission($this->object->getId(), $this->rolf_ref_id);
			$parentRoles = $rbacreview->getParentRoleIds($this->rolf_ref_id,true);
			$rbacadmin->copyRolePermission($_POST["adopt"],$parentRoles[$_POST["adopt"]]["parent"],
										   $this->rolf_ref_id,$this->object->getId());		

			// update object data entry (to update last modification date)
			$this->object->update();

			// send info
			$obj_data =& $this->ilias->obj_factory->getInstanceByObjId($_POST["adopt"]);
			sendInfo($this->lng->txt("msg_perm_adopted_from1")." '".$obj_data->getTitle()."'.<br/>".$this->lng->txt("msg_perm_adopted_from2"),true);
		}

		ilUtil::redirect("adm_object.php?ref_id=".$this->rolf_ref_id."&obj_id=".$this->object->getId()."&cmd=perm");
	}

	/**
	* wrapper for renamed function
	*
	* @access	public
	*/
	function assignSaveObject()
	{
        $this->assignUserObject();
    }

	/**
	* assign users to role
	*
	* @access	public
	*/
	function assignUserObject()
	{
    	global $rbacsystem, $rbacadmin, $rbacreview;

		if (!$rbacsystem->checkAccess("edit_userassignment", $this->rolf_ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_assign_user_to_role"),$this->ilias->error_obj->MESSAGE);
		}

		if (!$rbacreview->isAssignable($this->object->getId(),$this->rolf_ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("err_role_not_assignable"),$this->ilias->error_obj->MESSAGE);
		}

		if (!$rbacsystem->checkAccess('write',$this->rolf_ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_perm"),$this->ilias->error_obj->MESSAGE);
		}

		if (!isset($_POST["user"]))
		{
			sendInfo($this->lng->txt("no_checkbox"));
			$this->searchObject();

			return false;
		}
		
		$selected_users = $_POST["user"];
		$assigned_users_all = $rbacreview->assignedUsers($this->object->getId());
				
		// users to assign
		$assigned_users_new = array_diff($selected_users,array_intersect($selected_users,$assigned_users_all));
		
		// selected users all already assigned. stop
        if (count($assigned_users_new) == 0)
		{
			sendInfo($this->lng->txt("msg_selected_users_already_assigned"));
			$this->searchObject();
			
			return false;
		}

//	var_dump("<pre>",$assigned_users_all,$selected_users,$assigned_users_new,$online_users_all,$online_affected_users,"</pre>");exit;

		// assign new users
        foreach ($assigned_users_new as $user)
		{
			$rbacadmin->assignUser($this->object->getId(),$user,false);
        }
        
        // update session for newly assigned users online
        $this->object->_updateSessionRoles($assigned_users_new);

    	// update object data entry (to update last modification date)
		$this->object->update();

		sendInfo($this->lng->txt("msg_userassignment_changed"),true);
		ilUtil::redirect("adm_object.php?ref_id=".$this->rolf_ref_id."&obj_id=".$this->object->getId()."&cmd=userassignment&sort_by=".$_GET["sort_by"]."&sort_order=".$_GET["sort_order"]."&offset=".$_GET["offset"]);
	}
	
	/**
	* de-assign users from role
	*
	* @access	public
	*/
	function deassignUserObject()
	{
    	global $rbacsystem, $rbacadmin, $rbacreview;

		if (!$rbacsystem->checkAccess("edit_userassignment", $this->rolf_ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_assign_user_to_role"),$this->ilias->error_obj->MESSAGE);
		}

		if (!$rbacsystem->checkAccess('write',$this->rolf_ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_perm"),$this->ilias->error_obj->MESSAGE);
		}
		
    	$selected_users = ($_POST["user_id"]) ? $_POST["user_id"] : array($_GET["user_id"]);

		if ($selected_users[0]=== NULL)
		{
			$this->ilias->raiseError($this->lng->txt("no_checkbox"),$this->ilias->error_obj->MESSAGE);
		}

		// prevent unassignment of system user from system role
		if ($this->object->getId() == SYSTEM_ROLE_ID)
		{
            if ($admin = array_search(SYSTEM_USER_ID,$selected_users) !== false)
			    unset($selected_users[$admin]);
		}
//var_dump("<pre>",SYSTEM_USER_ID,$admin,$_POST,$_GET,$selected_users,"</pre>");exit;

		// check for each user if the current role is his last global role before deassigning him
		$last_role = array();
		$global_roles = $rbacreview->getGlobalRoles();
		
		foreach ($selected_users as $user)
		{
			$assigned_roles = $rbacreview->assignedRoles($user);
			$assigned_global_roles = array_intersect($assigned_roles,$global_roles);

			if (count($assigned_roles) == 1 or (count($assigned_global_roles) == 1 and in_array($this->object->getId(),$assigned_global_roles)))
			{
				$userObj = $this->ilias->obj_factory->getInstanceByObjId($user);
				$last_role[$user] = $userObj->getFullName();
				unset($userObj);
			}
		}

		// raise error if last role was taken from a user...
		if (count($last_role) > 0)
		{
			$user_list = implode(", ",$last_role);
			$this->ilias->raiseError($this->lng->txt("msg_is_last_role").": ".$user_list."<br/>".$this->lng->txt("msg_min_one_role")."<br/>".$this->lng->txt("action_aborted"),$this->ilias->error_obj->MESSAGE);
		}
		
		// ... else perform deassignment
		foreach ($selected_users as $user)
        {
			$rbacadmin->deassignUser($this->object->getId(),$user);
		}

        // update session for newly assigned users online
        $this->object->_updateSessionRoles($selected_users);

    	// update object data entry (to update last modification date)
		$this->object->update();

		sendInfo($this->lng->txt("msg_userassignment_changed"),true);
		ilUtil::redirect("adm_object.php?ref_id=".$this->rolf_ref_id."&obj_id=".$this->object->getId()."&cmd=userassignment&sort_by=".$_GET["sort_by"]."&sort_order=".$_GET["sort_order"]."&offset=".$_GET["offset"]);
	}
	
	/**
	* update role object
	* 
	* @access	public
	*/
	function updateObject()
	{
		global $rbacsystem, $rbacreview;

		// check write access
		if (!$rbacsystem->checkAccess("write", $this->rolf_ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_modify_role"),$this->ilias->error_obj->MESSAGE);
		}

		if (substr($this->object->getTitle(),0,3) != "il_")
		{
			// check required fields
			if (empty($_POST["Fobject"]["title"]))
			{
				$this->ilias->raiseError($this->lng->txt("fill_out_all_required_fields"),$this->ilias->error_obj->MESSAGE);
			}
	
			// check if role title has il_ prefix
			if (substr($_POST["Fobject"]["title"],0,3) == "il_")
			{
				$this->ilias->raiseError($this->lng->txt("msg_role_reserved_prefix"),$this->ilias->error_obj->MESSAGE);
			}
	
			// check if role title is unique
			if ($rbacreview->roleExists($_POST["Fobject"]["title"],$this->object->getId()))
			{
				$this->ilias->raiseError($this->lng->txt("msg_role_exists1")." '".ilUtil::stripSlashes($_POST["Fobject"]["title"])."' ".
										 $this->lng->txt("msg_role_exists2"),$this->ilias->error_obj->MESSAGE);
			}

			// update
			$this->object->setTitle(ilUtil::stripSlashes($_POST["Fobject"]["title"]));
		}

		$this->object->setDescription(ilUtil::stripSlashes($_POST["Fobject"]["desc"]));
		$this->object->setAllowRegister($_POST["Fobject"]["allow_register"]);
		$this->object->toggleAssignUsersStatus($_POST["Fobject"]["assign_users"]);
		$this->object->update();
		
		sendInfo($this->lng->txt("saved_successfully"),true);

		ilUtil::redirect("adm_object.php?ref_id=".$this->rolf_ref_id);
	}
	
	/**
	* edit object
	*
	* @access	public
	*/
	function editObject()
	{
		global $rbacsystem, $rbacreview;

		if (!$rbacsystem->checkAccess("write", $this->rolf_ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_write"),$this->ilias->error_obj->MESSAGE);
		}

		$this->getTemplateFile("edit");

		if ($_SESSION["error_post_vars"])
		{
			// fill in saved values in case of error
			if (substr($this->object->getTitle(),0,3) != "il_")
			{
				$this->tpl->setVariable("TITLE",ilUtil::prepareFormOutput($_SESSION["error_post_vars"]["Fobject"]["title"]),true);
			}
		
			$this->tpl->setVariable("DESC",ilUtil::stripSlashes($_SESSION["error_post_vars"]["Fobject"]["desc"]));
			$allow_register = ($_SESSION["error_post_vars"]["Fobject"]["allow_register"]) ? "checked=\"checked\"" : "";
			$assign_users = ($_SESSION["error_post_vars"]["Fobject"]["assign_users"]) ? "checked=\"checked\"" : "";
		}
		else
		{
			if (substr($this->object->getTitle(),0,3) != "il_")
			{
				$this->tpl->setVariable("TITLE",ilUtil::prepareFormOutput($this->object->getTitle()));
			}

			$this->tpl->setVariable("DESC",ilUtil::stripSlashes($this->object->getDescription()));
			$allow_register = ($this->object->getAllowRegister()) ? "checked=\"checked\"" : "";
			$assign_users = $this->object->getAssignUsersStatus() ? "checked=\"checked\"" : "";

		}

		$obj_str = "&obj_id=".$this->obj_id;

		$this->tpl->setVariable("TXT_TITLE",$this->lng->txt("title"));
		$this->tpl->setVariable("TXT_DESC",$this->lng->txt("desc"));
		
		// exclude allow register option for anonymous role, system role and all local roles
		$global_roles = $rbacreview->getGlobalRoles();

		$this->tpl->setVariable("FORMACTION", $this->getFormAction("update","adm_object.php?cmd=gateway&ref_id=".$this->rolf_ref_id.$obj_str));
		$this->tpl->setVariable("TXT_HEADER", $this->lng->txt($this->object->getType()."_edit"));
		$this->tpl->setVariable("TARGET", $this->getTargetFrame("update"));
		$this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
		$this->tpl->setVariable("TXT_SUBMIT", $this->lng->txt("save"));
		$this->tpl->setVariable("CMD_SUBMIT", "update");
		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
		
		if (substr($this->object->getTitle(),0,3) == "il_")
		{
			$this->tpl->setVariable("SHOW_TITLE",$this->object->getTitle());
		}

		if ($this->object->getId() != ANONYMOUS_ROLE_ID and 
			$this->object->getId() != SYSTEM_ROLE_ID and 
			in_array($this->object->getId(),$global_roles))
		{
			$this->tpl->setCurrentBlock("allow_register");
			$this->tpl->setVariable("TXT_ALLOW_REGISTER",$this->lng->txt("allow_register"));
			$this->tpl->setVariable("ALLOW_REGISTER",$allow_register);
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock("assign_users");
			$this->tpl->setVariable("TXT_ASSIGN_USERS",$this->lng->txt('allow_assign_users'));
			$this->tpl->setVariable("ASSIGN_USERS",$assign_users);
			$this->tpl->parseCurrentBlock();
		}
	}
	
	/**
	* display user assignment panel
	*/
	function userassignmentObject()
	{
		global $rbacreview, $rbacsystem;
		
		if (!$rbacsystem->checkAccess("edit_userassignment", $this->rolf_ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_assign_user_to_role"),$this->ilias->error_obj->MESSAGE);
		}

		$assigned_users = $rbacreview->assignedUsers($this->object->getId(),array("login","firstname","lastname","usr_id"));

		//if current user is admin he is able to add new members to group
		$val_contact = "<img src=\"".ilUtil::getImagePath("icon_pencil_b.gif")."\" alt=\"".$this->lng->txt("role_user_send_mail")."\" title=\"".$this->lng->txt("role_user_send_mail")."\" border=\"0\" vspace=\"0\"/>";
		$val_change = "<img src=\"".ilUtil::getImagePath("icon_change_b.gif")."\" alt=\"".$this->lng->txt("role_user_edit")."\" title=\"".$this->lng->txt("role_user_edit")."\" border=\"0\" vspace=\"0\"/>";
		$val_leave = "<img src=\"".ilUtil::getImagePath("icon_group_out_b.gif")."\" alt=\"".$this->lng->txt("role_user_deassign")."\" title=\"".$this->lng->txt("role_user_deassign")."\" border=\"0\" vspace=\"0\"/>";

		$counter = 0;

		foreach ($assigned_users as $user)
		{
			$link_contact = "mail_new.php?type=new&rcp_to=".$user["login"];
			$link_change = "adm_object.php?ref_id=7&obj_id=".$user["usr_id"]."&cmd=edit";
			$link_leave = $this->ctrl->getLinkTarget($this,"deassignUser")."&user_id=".$user["usr_id"];

            $member_functions = "";

            // exclude root/admin role and anon/anon
            if ($this->object->getId() != ANONYMOUS_ROLE_ID or $user["usr_id"] != ANONYMOUS_USER_ID)
			{
                //build function
                $member_functions = "<a href=\"".$link_contact."\">".$val_contact."</a>";
                $member_functions .= "<a href=\"".$link_change."\">".$val_change."</a>";

                if ($this->object->getId() != SYSTEM_ROLE_ID or $user["usr_id"] != SYSTEM_USER_ID)
                {
                    $member_functions .="<a href=\"".$link_leave."\">".$val_leave."</a>";
                }
            }

			// no check box for root/admin role and anon/anon
			if (($this->object->getId() == SYSTEM_ROLE_ID and $user["usr_id"] == SYSTEM_USER_ID)
                or ($this->object->getId() == ANONYMOUS_ROLE_ID and $user["usr_id"] == ANONYMOUS_USER_ID))
			{
                $result_set[$counter][] = "";
            }
            else
            {
                $result_set[$counter][] = ilUtil::formCheckBox(0,"user_id[]",$user["usr_id"]);
            }

            $result_set[$counter][] = $user["login"];
			$result_set[$counter][] = $user["firstname"];
			$result_set[$counter][] = $user["lastname"];
			$result_set[$counter][] = $member_functions;

			++$counter;

			unset($member_functions);
		}

		return $this->__showAssignedUsersTable($result_set);
    }
	
	function __showAssignedUsersTable($a_result_set)
	{
        global $rbacsystem;

		$actions = array("deassignUser"  => $this->lng->txt("remove"));

        $tbl =& $this->__initTableGUI();
		$tpl =& $tbl->getTemplateObject();

		$tpl->setCurrentBlock("tbl_form_header");
		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_row");


            $tpl->setCurrentBlock("plain_button");
		    $tpl->setVariable("PBTN_NAME","searchUserForm");
		    $tpl->setVariable("PBTN_VALUE",$this->lng->txt("role_add_user"));
		    $tpl->parseCurrentBlock();
		    $tpl->setCurrentBlock("plain_buttons");
		    $tpl->parseCurrentBlock();

			$tpl->setVariable("COLUMN_COUNTS",5);
			$tpl->setVariable("IMG_ARROW", ilUtil::getImagePath("arrow_downright.gif"));

            foreach ($actions as $name => $value)
			{
				$tpl->setCurrentBlock("tbl_action_btn");
				$tpl->setVariable("BTN_NAME",$name);
				$tpl->setVariable("BTN_VALUE",$value);
				$tpl->parseCurrentBlock();
			}

            $tpl->setVariable("TPLPATH",$this->tpl->tplPath);


		$this->ctrl->setParameter($this,"cmd","userassignment");


		// title & header columns
		$tbl->setTitle($this->lng->txt("assigned_users"),"icon_usr_b.gif",$this->lng->txt("users"));

		//user must be administrator
		$tbl->setHeaderNames(array("",$this->lng->txt("username"),$this->lng->txt("firstname"),$this->lng->txt("lastname"),$this->lng->txt("grp_options")));
		$tbl->setHeaderVars(array("","login","firstname","lastname","functions"),$this->ctrl->getParameterArray($this,"",false));
		$tbl->setColumnWidth(array("","30%","30%","30%","10%"));
		
		$this->__setTableGUIBasicData($tbl,$a_result_set,"userassignment");
		$tbl->render();
		$this->tpl->setVariable("ADM_CONTENT",$tbl->tpl->get());

		return true;
	}

	function &__initTableGUI()
	{
		include_once "class.ilTableGUI.php";

		return new ilTableGUI(0,false);
	}

	function __setTableGUIBasicData(&$tbl,&$result_set,$from = "")
	{
        switch($from)
		{
			case "group":
	           	$order = $_GET["sort_by"] ? $_GET["sort_by"] : "title";
				break;

			case "role":
	           	$order = $_GET["sort_by"] ? $_GET["sort_by"] : "title";
				break;

			default:
				// init sort_by (unfortunatly sort_by is preset with 'title')
	           	if ($_GET["sort_by"] == "title" or empty($_GET["sort_by"]))
                {
                    $_GET["sort_by"] = "login";
                }
                $order = $_GET["sort_by"];
				break;
		}

		$tbl->setOrderColumn($order);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setOffset($_GET["offset"]);
		$tbl->setLimit($_GET["limit"]);
		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));
		$tbl->setData($result_set);
	}

	function searchUserFormObject()
	{
		global $rbacsystem;

		if (!$rbacsystem->checkAccess("edit_userassignment", $this->rolf_ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_assign_user_to_role"),$this->ilias->error_obj->MESSAGE);
		}

		$this->lng->loadLanguageModule('search');

		$this->tpl->addBlockFile("ADM_CONTENT","adm_content","tpl.role_users_search.html");

		$this->tpl->setVariable("F_ACTION",$this->ctrl->getFormAction($this));
		$this->tpl->setVariable("SEARCH_ASSIGN_USR",$this->lng->txt("role_search_users"));
		$this->tpl->setVariable("SEARCH_SEARCH_TERM",$this->lng->txt("search_search_term"));
		$this->tpl->setVariable("SEARCH_VALUE",$_SESSION["role_search_str"] ? $_SESSION["role_search_str"] : "");
		$this->tpl->setVariable("SEARCH_FOR",$this->lng->txt("exc_search_for"));
		$this->tpl->setVariable("SEARCH_ROW_TXT_USER",$this->lng->txt("exc_users"));
		$this->tpl->setVariable("SEARCH_ROW_TXT_ROLE",$this->lng->txt("exc_roles"));
		$this->tpl->setVariable("SEARCH_ROW_TXT_GROUP",$this->lng->txt("exc_groups"));
		$this->tpl->setVariable("BTN2_VALUE",$this->lng->txt("cancel"));
		$this->tpl->setVariable("BTN1_VALUE",$this->lng->txt("search"));

        $usr = ($_POST["search_for"] == "usr" || $_POST["search_for"] == "") ? 1 : 0;
		$grp = ($_POST["search_for"] == "grp") ? 1 : 0;
		$role = ($_POST["search_for"] == "role") ? 1 : 0;

		$this->tpl->setVariable("SEARCH_ROW_CHECK_USER",ilUtil::formRadioButton($usr,"search_for","usr"));
		$this->tpl->setVariable("SEARCH_ROW_CHECK_ROLE",ilUtil::formRadioButton($role,"search_for","role"));
        $this->tpl->setVariable("SEARCH_ROW_CHECK_GROUP",ilUtil::formRadioButton($grp,"search_for","grp"));

		$this->__unsetSessionVariables();
	}

	function __unsetSessionVariables()
	{
		unset($_SESSION["role_delete_member_ids"]);
		unset($_SESSION["role_delete_subscriber_ids"]);
		unset($_SESSION["role_search_str"]);
		unset($_SESSION["role_search_for"]);
		unset($_SESSION["role_role"]);
		unset($_SESSION["role_group"]);
		unset($_SESSION["role_archives"]);
	}

	/**
	* cancelObject is called when an operation is canceled, method links back
	* @access	public
	*/
	function cancelObject()
	{
		$return_location = "userassignment";

		sendInfo($this->lng->txt("action_aborted"),true);
		ilUtil::redirect($this->ctrl->getLinkTarget($this,$return_location));
	}

	function searchObject()
	{
		global $rbacsystem, $tree;

		if (!$rbacsystem->checkAccess("edit_userassignment", $this->rolf_ref_id))
		{
			$this->ilias->raiseError($this->lng->txt("msg_no_perm_assign_user_to_role"),$this->ilias->error_obj->MESSAGE);
		}

		$_SESSION["role_search_str"] = $_POST["search_str"] = $_POST["search_str"] ? $_POST["search_str"] : $_SESSION["role_search_str"];
		$_SESSION["role_search_for"] = $_POST["search_for"] = $_POST["search_for"] ? $_POST["search_for"] : $_SESSION["role_search_for"];

		if(!isset($_POST["search_for"]) or !isset($_POST["search_str"]))
		{
			sendInfo($this->lng->txt("role_search_enter_search_string"));
			$this->searchUserFormObject();

			return false;
		}

		if(!count($result = $this->__search(ilUtil::stripSlashes($_POST["search_str"]),$_POST["search_for"])))
		{
			sendInfo($this->lng->txt("role_no_results_found"));
			$this->searchUserFormObject();

			return false;
		}

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.role_usr_selection.html");
		$this->__showButton("searchUserForm",$this->lng->txt("role_new_search"));

		$counter = 0;
		$f_result = array();

		switch($_POST["search_for"])
		{
        	case "usr":
				foreach($result as $user)
				{
					if(!$tmp_obj = ilObjectFactory::getInstanceByObjId($user["id"],false))
					{
						continue;
					}
					$f_result[$counter][] = ilUtil::formCheckbox(0,"user[]",$user["id"]);
					$f_result[$counter][] = $tmp_obj->getLogin();
					$f_result[$counter][] = $tmp_obj->getFirstname();
					$f_result[$counter][] = $tmp_obj->getLastname();

					unset($tmp_obj);
					++$counter;
				}
				$this->__showSearchUserTable($f_result);

				return true;

			case "role":
				foreach($result as $role)
				{
                    // exclude anonymous role
                    if ($role["id"] == ANONYMOUS_ROLE_ID)
                    {
                        continue;
                    }

                    if(!$tmp_obj = ilObjectFactory::getInstanceByObjId($role["id"],false))
					{
						continue;
					}

				    // exclude roles with no users assigned to
                    if ($tmp_obj->getCountMembers() == 0)
                    {
                        continue;
                    }

					$f_result[$counter][] = ilUtil::formCheckbox(0,"role[]",$role["id"]);
					$f_result[$counter][] = array($tmp_obj->getTitle(),$tmp_obj->getDescription());
					$f_result[$counter][] = $tmp_obj->getCountMembers();

					unset($tmp_obj);
					++$counter;
				}

				$this->__showSearchRoleTable($f_result);

				return true;

			case "grp":
				foreach($result as $group)
				{
					if(!$tree->isInTree($group["id"]))
					{
						continue;
					}

					if(!$tmp_obj = ilObjectFactory::getInstanceByRefId($group["id"],false))
					{
						continue;
					}

                    // exclude myself :-)
                    if ($tmp_obj->getId() == $this->object->getId())
                    {
                        continue;
                    }

					$f_result[$counter][] = ilUtil::formCheckbox(0,"group[]",$group["id"]);
					$f_result[$counter][] = array($tmp_obj->getTitle(),$tmp_obj->getDescription());
					$f_result[$counter][] = $tmp_obj->getCountMembers();

					unset($tmp_obj);
					++$counter;
				}
				$this->__showSearchGroupTable($f_result);

				return true;
		}
	}

	function __search($a_search_string,$a_search_for)
	{
		include_once("class.ilSearch.php");

		$this->lng->loadLanguageModule("content");
		$search =& new ilSearch($_SESSION["AccountId"]);
		$search->setPerformUpdate(false);
		$search->setSearchString(ilUtil::stripSlashes($a_search_string));
		$search->setCombination("and");
		$search->setSearchFor(array(0 => $a_search_for));
		$search->setSearchType('new');

		if($search->validate($message))
		{
			$search->performSearch();
		}
		else
		{
			sendInfo($message,true);
			$this->ctrl->redirect($this,"searchUserForm");
		}

		return $search->getResultByType($a_search_for);
	}

	function __showSearchUserTable($a_result_set,$a_cmd = "search")
	{
        $return_to  = "searchUserForm";

    	if ($a_cmd == "listUsersRole" or $a_cmd == "listUsersGroup")
    	{
            $return_to = "search";
        }

		$tbl =& $this->__initTableGUI();
		$tpl =& $tbl->getTemplateObject();

		// SET FORMACTION
		$tpl->setCurrentBlock("tbl_form_header");
		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME",$return_to);
		$tpl->setVariable("BTN_VALUE",$this->lng->txt("back"));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME","assignUser");
		$tpl->setVariable("BTN_VALUE",$this->lng->txt("add"));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_row");
		$tpl->setVariable("COLUMN_COUNTS",4);
		$tpl->setVariable("IMG_ARROW",ilUtil::getImagePath("arrow_downright.gif"));
		$tpl->parseCurrentBlock();

		$tbl->setTitle($this->lng->txt("role_header_edit_users"),"icon_usr_b.gif",$this->lng->txt("role_header_edit_users"));
		$tbl->setHeaderNames(array("",
								   $this->lng->txt("username"),
								   $this->lng->txt("firstname"),
								   $this->lng->txt("lastname")));
		$tbl->setHeaderVars(array("",
								  "login",
								  "firstname",
								  "lastname"),
							array("ref_id" => $this->rolf_ref_id,
                                  "obj_id" => $this->object->getId(),
								  "cmd" => $a_cmd,
								  "cmdClass" => "ilobjrolegui",
								  "cmdNode" => $_GET["cmdNode"]));

		$tbl->setColumnWidth(array("","33%","33%","33%"));

		$this->__setTableGUIBasicData($tbl,$a_result_set);
		$tbl->render();

		$this->tpl->setVariable("SEARCH_RESULT_TABLE",$tbl->tpl->get());

		return true;
	}

	function __showSearchRoleTable($a_result_set)
	{
		$tbl =& $this->__initTableGUI();
		$tpl =& $tbl->getTemplateObject();

		$tpl->setCurrentBlock("tbl_form_header");
		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME","searchUserForm");
		$tpl->setVariable("BTN_VALUE",$this->lng->txt("back"));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME","listUsersRole");
		$tpl->setVariable("BTN_VALUE",$this->lng->txt("role_list_users"));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_row");
		$tpl->setVariable("COLUMN_COUNTS",4);
		$tpl->setVariable("IMG_ARROW",ilUtil::getImagePath("arrow_downright.gif"));
		$tpl->parseCurrentBlock();

		$tbl->setTitle($this->lng->txt("role_header_edit_users"),"icon_usr_b.gif",$this->lng->txt("role_header_edit_users"));
		$tbl->setHeaderNames(array("",
								   $this->lng->txt("obj_role"),
								   $this->lng->txt("role_count_users")));
		$tbl->setHeaderVars(array("",
								  "title",
								  "nr_members"),
							array("ref_id" => $this->rolf_ref_id,
                                  "obj_id" => $this->object->getId(),
								  "cmd" => "search",
								  "cmdClass" => "ilobjrolegui",
								  "cmdNode" => $_GET["cmdNode"]));

		$tbl->setColumnWidth(array("","80%","19%"));


		$this->__setTableGUIBasicData($tbl,$a_result_set,"role");
		$tbl->render();

		$this->tpl->setVariable("SEARCH_RESULT_TABLE",$tbl->tpl->get());

		return true;
	}

	function __showSearchGroupTable($a_result_set)
	{
    	$tbl =& $this->__initTableGUI();
		$tpl =& $tbl->getTemplateObject();

		$tpl->setCurrentBlock("tbl_form_header");
		$tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME","searchUserForm");
		$tpl->setVariable("BTN_VALUE",$this->lng->txt("back"));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_btn");
		$tpl->setVariable("BTN_NAME","listUsersGroup");
		$tpl->setVariable("BTN_VALUE",$this->lng->txt("grp_list_users"));
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("tbl_action_row");
		$tpl->setVariable("COLUMN_COUNTS",4);
		$tpl->setVariable("IMG_ARROW",ilUtil::getImagePath("arrow_downright.gif"));
		$tpl->parseCurrentBlock();

		$tbl->setTitle($this->lng->txt("grp_header_edit_members"),"icon_usr_b.gif",$this->lng->txt("grp_header_edit_members"));
		$tbl->setHeaderNames(array("",
								   $this->lng->txt("obj_grp"),
								   $this->lng->txt("grp_count_members")));
		$tbl->setHeaderVars(array("",
								  "title",
								  "nr_members"),
							array("ref_id" => $this->rolf_ref_id,
                                  "obj_id" => $this->object->getId(),
								  "cmd" => "search",
								  "cmdClass" => "ilobjrolegui",
								  "cmdNode" => $_GET["cmdNode"]));

		$tbl->setColumnWidth(array("","80%","19%"));


		$this->__setTableGUIBasicData($tbl,$a_result_set,"group");
		$tbl->render();

		$this->tpl->setVariable("SEARCH_RESULT_TABLE",$tbl->tpl->get());

		return true;
	}

	function listUsersRoleObject()
	{
		global $rbacsystem,$rbacreview;

		$_SESSION["role_role"] = $_POST["role"] = $_POST["role"] ? $_POST["role"] : $_SESSION["role_role"];

		if(!is_array($_POST["role"]))
		{
			sendInfo($this->lng->txt("role_no_roles_selected"));
			$this->searchObject();

			return false;
		}

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.role_usr_selection.html");
		$this->__showButton("searchUserForm",$this->lng->txt("role_new_search"));

		// GET ALL MEMBERS
		$members = array();
		foreach($_POST["role"] as $role_id)
		{
			$members = array_merge($rbacreview->assignedUsers($role_id),$members);
		}

		$members = array_unique($members);

		// FORMAT USER DATA
		$counter = 0;
		$f_result = array();
		foreach($members as $user)
		{
			if(!$tmp_obj = ilObjectFactory::getInstanceByObjId($user,false))
			{
				continue;
			}
			// TODO: exclude anonymous user
			$f_result[$counter][] = ilUtil::formCheckbox(0,"user[]",$user);
			$f_result[$counter][] = $tmp_obj->getLogin();
			$f_result[$counter][] = $tmp_obj->getFirstname();
			$f_result[$counter][] = $tmp_obj->getLastname();

			unset($tmp_obj);
			++$counter;
		}
		$this->__showSearchUserTable($f_result,"listUsersRole");

		return true;
	}

	function listUsersGroupObject()
	{
		global $rbacsystem,$rbacreview,$tree;

		$_SESSION["role_group"] = $_POST["group"] = $_POST["group"] ? $_POST["group"] : $_SESSION["role_group"];

		if(!is_array($_POST["group"]))
		{
			sendInfo($this->lng->txt("role_no_groups_selected"));
			$this->searchObject();

			return false;
		}

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.role_usr_selection.html");
		$this->__showButton("searchUserForm",$this->lng->txt("role_new_search"));

		// GET ALL MEMBERS
		$members = array();
		foreach($_POST["group"] as $group_id)
		{
			if (!$tree->isInTree($group_id))
			{
				continue;
			}
			if (!$tmp_obj = ilObjectFactory::getInstanceByRefId($group_id))
			{
				continue;
			}

			$members = array_merge($tmp_obj->getGroupMemberIds(),$members);

			unset($tmp_obj);
		}

		$members = array_unique($members);

		// FORMAT USER DATA
		$counter = 0;
		$f_result = array();
		foreach($members as $user)
		{
			if(!$tmp_obj = ilObjectFactory::getInstanceByObjId($user,false))
			{
				continue;
			}
			$f_result[$counter][] = ilUtil::formCheckbox(0,"user[]",$user);
			$f_result[$counter][] = $tmp_obj->getLogin();
			$f_result[$counter][] = $tmp_obj->getFirstname();
			$f_result[$counter][] = $tmp_obj->getLastname();

			unset($tmp_obj);
			++$counter;
		}
		$this->__showSearchUserTable($f_result,"listUsersGroup");

		return true;
	}
} // END class.ilObjRoleGUI
?>
