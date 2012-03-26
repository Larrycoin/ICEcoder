<?php include("settings.php");?>
<?php

// Establish the full file path reference
$file=$_GET['file'];
if (isset($_GET['saveType'])) {$saveType = $_GET['saveType'];};
$docRoot = str_replace("\\","/",$_SERVER['DOCUMENT_ROOT']);

// Not done the first time we are on the save loop (ie, before the form posting reload)
if ($_GET['action']=="load"||$_GET['action']=="newFolder"||$_GET['action']=="rename"||$_GET['action']=="delete"||isset($_POST['contents'])) {
	$file= str_replace("|","/",$file);
}

// If we're due to open a file...
if ($_GET['action']=="load") {
	echo '<script>action="load";</script>';

	// Determine what to do based on filename
	// Everything is opened as text in the editor unless specified otherwise
	$fileType="text";
	if (strpos($file,".jpg")>0||strpos($file,".jpeg")>0||strpos($file,".gif")>0||strpos($file,".png")>0) {$fileType="image";};

	if ($fileType=="text") {
		$bannedFile=false;
		for ($i=0;$i<count($restrictedFiles);$i++) {
			if (strpos($file,$restrictedFiles[$i])!="") {
				$bannedFile=true;
			}
		}
		if ($_SESSION['userLevel'] == 10 || ($_SESSION['userLevel'] < 10 && $bannedFile==false)) {
			echo '<script>fileType="text";</script>';
			$loadedFile = file_get_contents($file);
			echo '<textarea name="loadedFile" id="loadedFile">'.str_replace("</textarea>","<ICEcoder:/:textarea>",$loadedFile).'</textarea>';
		} else {
			echo '<script>fileType="nothing";</script>';
			echo '<script>alert(\'Sorry, you need a higher admin level to view this file\');</script>';
		}
	};

	if ($fileType=="image") {
		echo '<script>fileType="image";fileName=\''.$file.'\'</script>';
	};
};

// If we're due to add a new folder...
if ($_GET['action']=="newFolder") {
	if ($_SESSION['userLevel'] > 0) {
		mkdir($docRoot.$file, 0707);
		// Reload file manager
		$fileName = substr($file,strrpos($file,"/")+1);
		$fileLoc = substr($file,0,strrpos($file,"/"));
		if ($fileLoc=="") {$fileLoc = "/";};
		echo '<script>top.ICEcoder.selectedFiles=[];top.ICEcoder.updateFileManagerList(\'add\',\''.$fileLoc.'\',\''.$fileName.'\');top.ICEcoder.serverMessage();top.ICEcoder.serverQueue("del",0);action="newFolder";</script>';
	} else {
		if (!is_writable($docRoot.$file)) {
			echo "<script>alert('Sorry, cannot create folder at\\n".substr($file,0,strrpos($file,"/"))."');</script>";
		} else {
			echo '<script>alert(\'Sorry, you need to be logged in to add folders\');</script>';
		}
		echo '<script>top.ICEcoder.serverMessage();top.ICEcoder.serverQueue("del",0);action="nothing";</script>';
	}
}

// If we're due to rename a file...
if ($_GET['action']=="rename") {
	if ($_SESSION['userLevel'] > 0 && is_writable($_GET['oldFileName'])) {
		rename($_GET['oldFileName'],$docRoot.$file);
		// Reload file manager
		$fileName = substr($file,strrpos($file,"/")+1);
		$fileLoc = substr($file,0,strrpos($file,"/"));
		if ($fileLoc=="") {$fileLoc = "/";};
		echo '<script>top.ICEcoder.selectedFiles=[];top.ICEcoder.updateFileManagerList(\'rename\',\''.$fileLoc.'\',\''.$fileName.'\');top.ICEcoder.serverMessage();top.ICEcoder.serverQueue("del",0);action="rename";</script>';
	} else {
		if (!is_writable($_GET['oldFileName'])) {
			echo "<script>alert('Sorry, cannot rename\\n".$_GET['oldFileName']."');</script>";
		} else {
			echo '<script>alert(\'Sorry, you need to be logged in to rename\');</script>';
		}
		echo '<script>top.ICEcoder.serverMessage();top.ICEcoder.serverQueue("del",0);action="nothing";</script>';
	}
}

// If we're due to delete a file...
if ($_GET['action']=="delete") {
	if ($_SESSION['userLevel'] > 0) {
		$filesArray = split(";",$file); // May contain more than one file here
		for ($i=0;$i<=count($filesArray)-1;$i++) {
			if (is_writable($docRoot.$filesArray[$i])) {
				if (is_dir($docRoot.$filesArray[$i])) { 
					rrmdir($docRoot.$filesArray[$i]);
				} else {
					unlink($docRoot.$filesArray[$i]);
				}
				// Reload file manager
				$fileName = substr($file,strrpos($file,"/")+1);
				$fileLoc = substr($file,0,strrpos($file,"/"));
				if ($fileLoc=="") {$fileLoc = "/";};
				echo '<script>top.ICEcoder.selectedFiles=[];top.ICEcoder.updateFileManagerList(\'delete\',\''.$fileLoc.'\',\''.$fileName.'\');top.ICEcoder.serverMessage();top.ICEcoder.serverQueue("del",0);action="delete";</script>';
			} else {
				echo "<script>alert('Sorry can\\'t delete\\n".$filesArray[$i]."');</script>";
			}
			echo '<script>top.ICEcoder.serverMessage();top.ICEcoder.serverQueue("del",0);action="nothing";</script>';
		}
	} else {
		if (!is_writable($docRoot.$filesArray[$i])) {
			echo "<script>alert('Sorry, cannot delete\\n".$docRoot.$filesArray[$i]."');</script>";
		} else {
			echo '<script>alert(\'Sorry, you need to be logged in to delete\');</script>';
		}
		echo '<script>top.ICEcoder.serverMessage();top.ICEcoder.serverQueue("del",0);action="nothing";</script>';
	}
}

// The function to recursively remove folders & files
function rrmdir($dir) { 
	if (is_dir($dir)) { 
		$objects = scandir($dir); 
		foreach ($objects as $object) { 
			if ($object != "." && $object != "..") { 
				if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object); 
			} 
		} 
		reset($objects); 
	rmdir($dir); 
	} 
} 

if ($_GET['action']=="save") {
	echo '<script>action="save";</script>';
	// on the form posting via a reload, save the file
	if (isset($_POST['contents'])) {
		if ($_SESSION['userLevel'] > 0) {
			if (isset($_POST['newFileName'])&&$_POST['newFileName']!="") {
				$file = $_POST['newFileName'];
			}
			$saveFile = str_replace("\\","/",$_SERVER['DOCUMENT_ROOT']).$file;
			$saveFile = str_replace("//","/",$saveFile);
			if ((file_exists($saveFile) && is_writable($saveFile)) || $_POST['newFileName']!="") {
				$fh = fopen($saveFile, 'w') or die("can't open file");
				fwrite($fh, $_POST['contents']);
				fclose($fh);

				if (isset($_POST['newFileName'])&&$_POST['newFileName']!="") {
					// Reload file manager & stop CTRL+s being sticky
					$fileName = substr($file,strrpos($file,"/")+1);
					$fileLoc = substr($file,0,strrpos($file,"/"));
					if ($fileLoc=="") {$fileLoc = "/";};
					echo '<script>top.ICEcoder.selectedFiles=[];top.ICEcoder.updateFileManagerList(\'add\',\''.$fileLoc.'\',\''.$fileName.'\')</script>';
				}
				if (isset($_POST['newFileName'])&&$_POST['newFileName']!="") {
					echo '<script>top.ICEcoder.renameTab(top.ICEcoder.selectedTab,\''.$file.'\');</script>';
				}
				echo '<script>top.ICEcoder.serverMessage();top.ICEcoder.serverQueue("del",0);action="doneSave";</script>';
        		} else {
				echo "<script>alert('Sorry, cannot write\\n".$file."');</script>";
				echo '<script>top.ICEcoder.serverMessage();top.ICEcoder.serverQueue("del",0);action="nothing";</script>';
			}
		} else {
			if (!is_writable($saveFile)) {
				echo "<script>alert('Sorry, cannot write\\n".$file."');</script>";
			} else {
				echo '<script>alert(\'Sorry, you need to be logged in to save\');</script>';
			}
			echo '<script>top.ICEcoder.serverMessage();top.ICEcoder.serverQueue("del",0);action="nothing";</script>';
		}
	}
};
?>
<script>
if (action=="load") {
	if (fileType=="text") {
		top.ICEcoder.loadingFile = true;
		// Reset the various states back to their initial setting
		selectedTab = top.ICEcoder.openFiles.length;	// The tab that's currently selected

		// Finally, store all data, show tabs etc
		top.ICEcoder.createNewTab();
		top.ICEcoder.cMInstances.push(top.ICEcoder.nextcMInstance);
		top.ICEcoder.content.contentWindow.createNewCMInstance(top.ICEcoder.nextcMInstance);

		// Set the value & innerHTML of the code textarea to that of our loaded file plus make it visible (it's hidden on _coder's load)
		top.ICEcoder.switchMode();
		cM = top.ICEcoder.getcMInstance();
		cM.setValue(document.getElementById('loadedFile').value);
		top.document.getElementById('content').style.visibility='visible';
		top.ICEcoder.switchTab(top.ICEcoder.selectedTab);
		cM.focus();

		// Then clean it up, set the text cursor, update the display and get the character data
		top.ICEcoder.contentCleanUp();
		top.ICEcoder.content.contentWindow['cM'+top.ICEcoder.cMInstances[top.ICEcoder.selectedTab-1]].setLineClass(top.ICEcoder['cMActiveLine'+top.ICEcoder.selectedTab], null);
		top.ICEcoder['cMActiveLine'+top.ICEcoder.selectedTab] = top.ICEcoder.content.contentWindow['cM'+top.ICEcoder.cMInstances[top.ICEcoder.selectedTab-1]].setLineClass(0, "cm-s-activeLine");
		top.ICEcoder.nextcMInstance++;
		top.ICEcoder.serverMessage();
		top.ICEcoder.serverQueue("del",0);
		top.ICEcoder.loadingFile = false;
	}

	if (fileType=="image") {
		top.document.getElementById('blackMask').style.visibility = "visible";
		top.document.getElementById('mediaContainer').innerHTML = "<img src=\"<?php echo str_replace($docRoot,"",$file);?>\" style=\"border: solid 10px #ffffff; max-width: 700px; max-height: 500px\" onClick=\"return false\"><br><span style=\"border: solid 10px #ffffff; background-color: #ffffff\" onClick=\"return false\"><?php echo str_replace($docRoot,"",$file);?></span>";
	}
}
</script>

<form name="saveFile" action="file-control.php?action=save&file=<?php if (isset($file)) {echo $file;};?>" method="POST">
<textarea name="contents"></textarea>
<input type="hidden" name="newFileName" value="">
</form>

<script>
if (action=="save") {
	<?php
	if ($file=="|[NEW]"||$saveType=="saveAs") {
	?>
		if (top.ICEcoder.rightClickedFile) {
			shortURL = top.ICEcoder.rightClickedFile.substr((top.ICEcoder.rightClickedFile.indexOf(top.shortURLStarts)+top.shortURLStarts.length),top.ICEcoder.rightClickedFile.length).replace(/\|/g,"/")+"/";
			newFileName = prompt('Enter Filename',shortURL);
		} else {
			newFileName = prompt('Enter Filename','/');
		}
		document.saveFile.newFileName.value = newFileName;
	<?php ;};?>
	document.saveFile.contents.innerHTML = top.document.getElementById('saveTemp1').value;
	document.saveFile.submit();
}
</script>

<script>
if (action=="doneSave") {
	top.ICEcoder.changedContent[top.ICEcoder.selectedTab-1] = 0;
	top.ICEcoder.redoTabHighlight(top.ICEcoder.selectedTab);
}
</script>