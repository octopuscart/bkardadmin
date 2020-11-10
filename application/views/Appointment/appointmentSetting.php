<?php
$this->load->view('layout/header');
$this->load->view('layout/topmenu');
?>    
<!-- ================== BEGIN PAGE LEVEL STYLE ================== -->
<link href="<?php echo base_url(); ?>assets/plugins/DataTables/css/data-table.css" rel="stylesheet" />
<!-- ================== END PAGE LEVEL STYLE ================== -->

<style>
    .appointmentheader div{
        font-size: 20px;
    }
    .appointmentfooter{
        border-bottom: 2px solid #000;
    }
    .list-group-item {
        position: relative;
        display: block;
        padding: 5px 3px;
        margin-bottom: -1px;
    }
</style>

<!-- Main content -->
<section class="content" ng-controller="AppointmentController">


    <div class="panel panel-inverse" data-sortable-id="ui-widget-2">
        <div class="panel-heading">
            <!--            <div class="btn-group pull-right">
                            <button type="button" class="btn btn-success btn-xs">Action</button>
                            <button type="button" class="btn btn-success btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu" role="menu">
                                <li><a href="#">Action</a></li>
                                <li><a href="#">Another action</a></li>
                                <li><a href="#">Something else here</a></li>
                                <li class="divider"></li>
                                <li><a href="#">Separated link</a></li>
                            </ul>
                        </div>-->
            <h4 class="panel-title">Appointment Data</h4>
        </div>
        <div class="panel-body">





            <table id="data-table" class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width:300px;">Event</th>
                       
                        <th>Venue & Address</th>
                        <th>Contact</th>

                        <th style="    width: 300px;">Date/Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (count($appointmentdata)) {
                        foreach ($appointmentdata as $key => $value) {
                            ?>

                            <tr>
                                <td>
                                    <b><?php echo $value['title']; ?></b><br/>
                                    <?php echo $value['description']; ?><br/>
                                    
                                   <br/>
                                    <br/>
                                    <b>Organizer</b>: <?php echo $value['manager']['email'] ?>
                                </td>

                                <td> <b>
                                        <i class="fa fa-building-o"></i>
                                        <span style="line-height: 14px;"> <?php echo $value['venue']; ?></span>
                                    </b>
                                    <br/>
                                    <small>
                                        <?php echo $value['address']; ?>
                                    </small>
                                    <div class="">
                                        <span style=" ">
                                            <i class="fa fa-phone"></i>  <?php echo $value['contact_no']; ?>
                                        </span>

                                    </div>
                                    <br/>
                                    <?php echo ucfirst(strtolower($value['city'])); ?>, 
                                    <?php echo ucfirst(strtolower($value['state'])); ?><br/>
                                    <?php echo ucfirst(strtolower($value['country'])); ?>
                                </td>
                                <td>
                                    <?php
                                    echo $value["email"];
                                    ?><br/>
                                    <?php
                                    echo $value["website"];
                                    ?>
                                </td>

                                <td>
                                    <ul class="list-group">
                                        <b> <?php
                                        echo $value["days"];
                                        ?></b>
                                    </ul>
                                      <br/>
                                    <br/>
                                    <button class="btn btn-primary btn-xs" ng-click="viewOnModal('<?php echo $value['address']; ?>', '<?php echo $value['venue']; ?>')"><i class='fa fa-map-marker'></i> View On Map</button>
                                    <a href="<?php echo site_url("Events/editEvent/" . $value['id']); ?>" class="btn btn-warning btn-xs" ><i class='fa fa-edit'></i> Edit</a>
                                    <a href="<?php echo site_url("Events/deleteEvent/" . $value['id']); ?>" class="btn btn-danger btn-xs" ><i class='fa fa-trash'></i> Delete</a>
                                   
                                </td>
                            </tr>



                            <?php
                        }
                    } else {
                        
                    }
                    ?>

                </tbody>
            </table>








        </div>
    </div>

    <div class="row">
        <div class="modal fade" id="modal-dialog-map">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                        <h4 class="modal-title">{{mapdata.hotel}}</h4>
                    </div>
                    <div class="modal-body">

                        <iframe  frameborder='0' scrolling='no'  marginheight='0' marginwidth='0'  height="200px" width="100%" id='mapifram'  src="">
                        </iframe> 
                    </div>
                    <div class="modal-footer">
                        <a href="javascript:;" class="btn btn-sm btn-white" data-dismiss="modal">Close</a>
                        <a href="javascript:;" class="btn btn-sm btn-success">Action</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="<?php echo base_url(); ?>assets/plugins/DataTables/js/jquery.dataTables.js"></script>
<!--datepicker-->
<script src="<?php echo base_url(); ?>assets/plugins/bootstrap-daterangepicker/moment.js"></script>
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/plugins/bootstrap-daterangepicker/daterangepicker-bs3.css" rel="stylesheet" />
<script src="<?php echo base_url(); ?>assets/plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
<!--end of datepicker-->
<script>
                            Admin.controller('AppointmentController', function ($scope, $http, $filter, $timeout) {
                                $scope.mapdata = {'address': '', 'hotal': '', 'url': ''};
                                $scope.viewOnModal = function (address, hotel) {
                                    $("#mapifram").attr("src", "https://maps.google.com/?q=" + hotel + "+" + address + "&output=embed")
                                    $scope.mapdata.address = address;
                                    $scope.mapdata.hotel = hotel;
                                    console.log($scope.mapdata);
                                    $("#modal-dialog-map").modal("show")
                                }
                            })
</script>
<?php
$this->load->view('layout/footer');
?> 
