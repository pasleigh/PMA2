/**
 * Created by cenpas on 04/04/2016.
 */
function validate_form() {
    var num_not_completed = 0;
    var dom_names_to_check = [];
    dom_names_to_check[0] = "f_title";
    dom_names_to_check[1] = "f_supervisor_id";
    dom_names_to_check[2] = "f_assocstaff_id";
    dom_names_to_check[3] = "f_outline";
    dom_names_to_check[4] = "f_PGprojects";

    // plus at least one of prog0-6
    // title
    var txtValue;
    txtValue = document.getElementById(dom_names_to_check[0]).value;
    //alert(dom_names_to_check[0] + ": " + txtValue);
    if(txtValue.length == 0){
        num_not_completed++;
    }

    // supervisor
    var list;
    var staffID;
    list = document.getElementById(dom_names_to_check[1]);
    //alert(dom_names_to_check[1] + ": " + list.selectedIndex + " : " + list[list.selectedIndex].value);
    staffID = (Number)(list[list.selectedIndex].value);
    if(staffID < 0){
        num_not_completed++;
    }

    // assocstaff_id
    list = document.getElementById(dom_names_to_check[2]);
    //alert(dom_names_to_check[2] + ": " + list.selectedIndex + " : " + list[list.selectedIndex].value);
    staffID = (Number)(list[list.selectedIndex].value);
    if(staffID < 0){
        num_not_completed++;
    }

    // outline
    txtValue = document.getElementById(dom_names_to_check[3]).value;
    //alert(dom_names_to_check[3] + ": " + txtValue);
    if(txtValue.length == 0){
        num_not_completed++;
    }

    // PGProjects
    txtValue = document.getElementById(dom_names_to_check[4]).value;
    //alert(dom_names_to_check[4] + ": " + txtValue);
    // don't need to do anything with this as it is always going to have a value
    // could check to see if greater than zero

    //alert("Num not completed = " + num_not_completed );
    //return false;
    if (num_not_completed == 0 ) {
        //alert("All done.");
        return true;
    } else {
        if(num_not_completed == 1){
            alert(num_not_completed + " section needs answering.\nComplete this before you can submit the project.");
        }else{
            alert(num_not_completed + " sections need answering.\nComplete these before you can submit the project.");
        }
        //alert(num_not_selected + " questions and " +  num_not_enough_feedback + " feedback still need to be completed.");
    }
    return false;

}

