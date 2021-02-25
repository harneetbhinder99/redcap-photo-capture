<?php

/**
 * @file
 * Provides ExternalModule class for Multi-DET module.
 */

namespace MCRI\PhotoCapture;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use REDCap;

define("ATTEMPT_LIMIT", 30);

/**
 * PhotoCapture class for PhotoCapture.
 */
class PhotoCapture extends AbstractExternalModule
{


  /**
   * @inheritdoc
   */
  function redcap_data_entry_form_top($project_id, $record = null, $instrument, $event_id, $group_id = null, $repeat_instance = 1)
  {
    $this->is_survey = false;
    $this->loadDeviceCamera($instrument);
  }
  /**
   * @inheritdoc
   */
  function redcap_survey_page_top($project_id, $record = null, $instrument, $event_id, $group_id = null, $survey_hash, $response_id = null, $repeat_instance = 1)
  {
    $this->is_survey = true;
    $this->loadDeviceCamera($instrument);
  }
  function loadDeviceCamera($instrument)
  {

    //get target upload field from config
    $target_fields = AbstractExternalModule::getProjectSetting('phca_target_upload_field');
?>
    <style>
      /* CSS comes here */
      .contentarea .PhotoCapture_video {
        width: 200px;
        height: 150px;
      }

      .contentarea .PhotoCapture_photo {
        width: 200px;
        height: 150px;
        vertical-align: unset;
      }


      .contentarea .PhotoCapture_camera {
        width: 240px;
        display: inline-block;
      }

      .contentarea .PhotoCapture_output {
        width: 240px;
        display: inline-block;
      }


      .contentarea {
        font-size: 16px;
        font-family: Arial;
        text-align: center;
      }

      <?php foreach ($target_fields as $fld) {
        echo '#fileupload-container-' . $fld . ' .fileuploadlink {display: none;}';
        echo '#fileupload-container-' . $fld . ' .fileuploadlink:first-of-type+span {display: none}';
        echo '#fileupload-container-' . $fld . ' .sendit-lnk {display: none}';
        echo '#fileupload-container-' . $fld . ' .filedownloadlink {display: none!important}';
      }
      ?>
    </style>



    <script>
      (function() {
        window.addEventListener('load', (event) => {

          /* JS comes here */
          var width = 200; // We will scale the photo width to this
          var height = 0; // This will be computed based on the input stream


          <?php foreach ($target_fields as $fld) {  ?>

            activateDisplay="";
            if($("#fileupload-container-<?php echo $fld; ?> .deletedoc-lnk").length>0){
              activateDisplay="display:none"
            }

            $("#fileupload-container-<?php echo $fld; ?>").before(`<button type="button" id="<?php echo $fld; ?>_activbutton" style='`+activateDisplay+`'  class="btn btn-defaultrc btn-sm PhotoCapture_activbutton">Activate Camera</button>
            <button type="button" id="<?php echo $fld; ?>_deactivatebutton" style='display:none' class="btn btn-defaultrc btn-sm PhotoCapture_activbutton">Deactivate Camera</button><div style="color: green;display:none" class="infomsgss"><i  class="fas fa-check mr-1 fs12"></i> Image saved successfuly</div>`);


            $('#form :input[name="<?php echo $fld; ?>"]').change(() => {
              if ($('#form :input[name="<?php echo $fld; ?>"]').val() == "") {
                clearphoto('<?php echo $fld; ?>');
                $("#<?php echo $fld; ?>_activbutton").show();

              } else {
                $("#<?php echo $fld; ?>_contentarea").hide()
                $("#<?php echo $fld; ?>_deactivatebutton").hide()
                $("#fileupload-container-<?php echo $fld; ?>").parent().find(".infomsgss").show();
                DeactivateCamera('<?php echo $fld; ?>')
                setTimeout(function() {
                  $('.infomsgss').hide();
                }, 2000);
              }

            });

            $("#<?php echo $fld; ?>_activbutton").click(() => {
              $(".contentarea").hide();
              $("#<?php echo $fld; ?>_activbutton").hide()
              if ($("#<?php echo $fld; ?>_contentarea").length > 0) {
                $("#<?php echo $fld; ?>_contentarea").show();
                $("#<?php echo $fld; ?>_Save").hide();
                startup("<?php echo $fld; ?>");
                $("#<?php echo $fld; ?>_deactivatebutton").show();
              } else {
                ActivateCamera($("#<?php echo $fld; ?>_activbutton"), '<?php echo $fld; ?>');
              }

            })
            $("#<?php echo $fld; ?>_deactivatebutton").click(() => {
              DeactivateCamera('<?php echo $fld; ?>');
              $("#<?php echo $fld; ?>_activbutton").show()
              $("#<?php echo $fld; ?>_deactivatebutton").hide()
              $("#<?php echo $fld; ?>_contentarea").hide()

            })
          <?php } ?>

          function dataURLtoFile(dataurl, filename) {

            var arr = dataurl.split(','),
              mime = arr[0].match(/:(.*?);/)[1],
              bstr = atob(arr[1]),
              n = bstr.length,
              u8arr = new Uint8Array(n);

            while (n--) {
              u8arr[n] = bstr.charCodeAt(n);
            }

            return new File([u8arr], filename, {
              type: mime
            });
          }

          function DeactivateCamera(paramName) {
            videoElem = document.getElementById(paramName + '_video');
            const stream = videoElem.srcObject;
            const tracks = stream.getTracks();

            tracks.forEach(function(track) {
              track.stop();
            });

            videoElem.srcObject = null;
          }

          function ActivateCamera(ref, parmName) {
            filePopUp("phca")
            $('#file_upload').dialog({
              title: "phca",
              bgiframe: true,
              modal: true,
              width: (isMobileDevice ? $('#questiontable').width() : 500)
            }).dialog('close');
            $(ref).hide();
            $("#" + parmName + "_deactivatebutton").show();
            $("#fileupload-container-" + parmName).after(`
        <form class="PhotoCapture_form" id="` + parmName + `_form">
    
    <input type="hidden" name="myfile_base64_edited" value="0">
    <input type="hidden" name="myfile_replace" value="0">
    <input type="hidden" name="field_name" value="` + parmName + `-linknew">
    <input type="hidden" name="redcap_csrf_token" value="">
        <div class="contentarea" id="` + parmName + `_contentarea" >
        <table>
        <tr>
        <td> <video class="PhotoCapture_video" id="` + parmName + `_video">Video stream not available.</video>
        </td>
        <td>
        <canvas class="PhotoCapture_output" style="display:none !important"  id="` + parmName + `_canvas"></canvas>
        <img class="PhotoCapture_photo" id="` + parmName + `_photo" alt="The screen capture will appear in this box."></td>

        </tr>
        <tr>
        <td> <button type="button" id="` + parmName + `_startbutton" class="btn btn-defaultrc btn-sm">Take photo</button></td>
        <td> <button type="button" id="` + parmName + `_Save" class="btn btn-defaultrc btn-sm" style="display:none">Save photo</button></td>
        
        </tr></table></div>
        </form>`)
            appendCsrfTokenToForm();
            startup(parmName);
          }


          function startup(paramName) {
            var video = document.getElementById(paramName + '_video');
            var canvas = document.getElementById(paramName + '_canvas');
            var photo = document.getElementById(paramName + '_photo');
            var startbutton = document.getElementById(paramName + '_startbutton');
            var savebutton = document.getElementById(paramName + '_Save');
            navigator.mediaDevices.getUserMedia({
                video: true,
                audio: false
              })
              .then(function(stream) {
                video.srcObject = stream;
                video.play();
              })
              .catch(function(err) {
                console.log("An error occurred: " + err);
              });

            video.addEventListener('canplay', function(ev) {
              height = video.videoHeight / (video.videoWidth / width);

              if (isNaN(height)) {
                height = width / (4 / 3);
              }

              video.setAttribute('width', width);
              video.setAttribute('height', height);
              canvas.setAttribute('width', width);
              canvas.setAttribute('height', height);

            }, false);

            startbutton.addEventListener('click', function(ev) {
              takepicture(canvas, photo, video);
              $(savebutton).show();
              ev.preventDefault();
            }, false);
            savebutton.addEventListener('click', function(ev) {
              var form = document.getElementById(paramName + '_form');
              ImageURL = $(photo).attr("src");
              var formDataToUpload = new FormData(form);
              formDataToUpload.append("myfile", dataURLtoFile(ImageURL, "camera-image.png"));

              $.ajax({
                url: $("#form_file_upload").attr("action"),
                data: formDataToUpload,
                type: "POST",
                contentType: false,
                processData: false,
                cache: false,
                dataType: "text",
                success: function(data) {
                  $("body").append(data);
                }
              });


              ev.preventDefault();
            }, false);
            clearphoto(paramName);
          }


          function clearphoto(paramName) {
            try {
              var canvas = document.getElementById(paramName + '_canvas');
              var photo = document.getElementById(paramName + '_photo');
              var context = canvas.getContext('2d');
              context.fillStyle = "#f5f5f5";
              context.fillRect(0, 0, canvas.width, canvas.height);

              var data = canvas.toDataURL('image/png');
              photo.setAttribute('src', data);
            } catch (error) {

            }

          }

          function takepicture(canvas, photo, video) {
            var context = canvas.getContext('2d');
            if (width && height) {
              canvas.width = width;
              canvas.height = height;
              context.drawImage(video, 0, 0, width, height);

              var data = canvas.toDataURL('image/png');
              photo.setAttribute('src', data);
            } else {
              clearphoto();
            }
          }

        });
      })();
    </script>

<?php

  }
}