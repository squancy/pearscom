function iFrameOn(){
	richTextField.document.designMode = 'On';
}

function iBold(){
	richTextField.document.execCommand('bold', false, null);
}

function iUnderline(){
	richTextField.document.execCommand('underline', false, null);
}

function iItalic(){
	richTextField.document.execCommand('italic', false, null);
}

function iFontSize(){
	var size = prompt('Enter a size 1 - 7', '');
	richTextField.document.execCommand('FontSize', false,size);
}

function iForeColor(){
	var color = prompt('Define a basic color or apply a hexadecimal value', '');
	richTextField.document.execCommand('ForeColor', false, color);
}

function iHorizontalRule(){
	richTextField.document.execCommand('inserthorizontalrule', false, null);
}

function iUnorderedList(){
	richTextField.document.execCommand('InsertUnorderedList', false, "newUL");
}

function iOrderedList(){
	richTextField.document.execCommand('InsertOrderedList', false, "newOL");
}

function iLink(){
	var linkURL = prompt("Enter the URL for this link:", "https://");
	richTextField.document.execCommand('CreateLink', false, linkURL);
}

function iUnlink(){
	richTextField.document.execCommand('Unlink', false, null);
}

function iJustCenter(){
	richTextField.document.execCommand('JustifyCenter', false, null);
}

function iJustLeft(){
	richTextField.document.execCommand('JustifyLeft', false, null);
}

function iJustRight(){
	richTextField.document.execCommand('JustifyRight', false, null);
}

function iImage(){
	var imgSrc = prompt('Enter image location', '');
	if(imgSrc != null){
		richTextField.document.execCommand('insertimage', false, imgSrc);
	}
}
