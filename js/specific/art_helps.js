/*
  Help dialog boxes.
*/

function prepareDialogArt() {
  _("overlay").style.display = "block";
  _("overlay").style.opacity = 0.5;
  _("dialogbox_art").style.display = "block";
  document.body.style.overflow = "hidden";
}

function openAHelp(){
  prepareDialogArt();
  _("dialogbox_art").innerHTML = `
    <p style="font-size: 16px; font-style: bold; text-align: center;">
      How to write a well-received, clean, entertaining formal article?
    </p>

    <hr class="dim">
    <br>

    <p>
      In order to write a good article you have to keep in mind the following things and
      instructions:
    </p>
    <br>
    <p>
      1. Once you have choosed a topic do a research of that to get a clear picture and
      enough knowledge
    </p>
    <p>
      2. Create a strong, unique title that will describe your article in a few words and
      will grab the readers&#39; attention</p>
    <p>
      3. Divide your article into more (at least 3) paragraphs: <i>introducion</i>,
      <i>main part</i>, <i>conclusion</i>
    </p>
    <p>4. Write major points</p>
    <p>
      5. Write your article first and edit it later</p>
      <br>
      <b>
        <p>
          Structure of a well-written formal article
        </p>
      </b>
    <p>
      The <i>introducion:</i>
    </p>
    <p style="margin: 0px;">
      it is one of the most essential part of the article - grab the attention of your
      readers, hook them in.
    </p>
    <p style="font-size: 12px !important; margin-left: 20px;">
      Use drama, emotion, quotations, rhetorical questions, descriptions, allusions,
      alliterations and methapors.
    </p>
    <br>
    <p>
      The <i>main part(s):
    </i>
    </p>
    <p>
      this part of the article needs to stick to the ideas or answer any questions
      raised in the intoducion
    </p>
    <p style="font-size: 12px !important; margin-left: 20px;">
      Try to maintain an "atmosphere" / tone / distinctive voice throughout the writing.
    </p>
    <br>
    <p>
      The <i>conclusion:</i>
    </p>
    <p>
      it is should be written to help the reader remember the article. Use a strong
      punch-line.
    </p>
    <br>
    <p style="font-size: 16px; text-align: center;">
      Images &amp; visualization in the topic for the better understanding
    </p>
    <hr class="dim">
    <p>
      Do research and a plan for your article
      (<a href="http://www.e-custompapers.com/blog/practical-tips-for-article-reviews.html"
        target="_blank">source</a>)
    </p>
    <img src="/images/howtoart.jpg">
    <p>
      The parts of a well-written article
      (<a href="https://apessay.com/order/?rid=ea55690ca8f7b080" target="_blank">source</a>)
    </p>
    <img src="/images/partsa.jpg">
    <button id="vupload" style="position: static; float: right; margin: 3px;"
      onclick="closeDialog_a()">
      Close
    </button>
  `;
}

function openIHelp(){
  prepareDialogArt();
	_("dialogbox_art").innerHTML = `
    <b style="font-size: 16px;">
      Attachable images
    </b>
    <hr class="dim">
    <p>
      You can attach up to 5 images to your article in order to make it more visually,
      helpful and picturesque. It is an optional avalibility but it is highly recommended
      to attach at least one picture to your article. If you do not attach any images nothing
      will appear instead of this.
      <br>
      <b>Important:</b> the rules are the same as with the standard image uploading.
      The maximum image size is 5MB and the allowed image extenstions are jpg, jpeg, gif and
      png. For more information please visit the help page.
    </p>
    <button id="vupload" style="float: right; margin: 3px;" onclick="closeDialog_a()">
      Close
    </button>
  `;
}
