<template>
  <div class="main-content">
    <breadcumb :page="$t('ReturnDetail')" :folder="$t('ListReturns')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <b-card v-if="!isLoading">
      <b-row>
        <b-col md="12" class="mb-5">
          <router-link
            v-if="currentUserPermissions && currentUserPermissions.includes('Sale_Returns_edit')"
            title="Edit"
            class="btn btn-success btn-icon ripple btn-sm"
            :to="'/app/sale_return/edit/'+$route.params.id+'/'+sale_return.sale_id"
          >
            <i class="i-Edit"></i>
            <span>{{$t('EditReturn')}}</span>
          </router-link>
         
          <button @click="Return_PDF()" class="btn btn-primary btn-icon ripple btn-sm">
            <i class="i-File-TXT"></i> PDF
          </button>
          <button @click="print()" class="btn btn-warning btn-icon ripple btn-sm">
            <i class="i-Billing"></i>
            {{$t('print')}}
          </button>
          <button
            v-if="currentUserPermissions && currentUserPermissions.includes('Sale_Returns_delete')"
            @click="Delete_Return()"
            class="btn btn-danger btn-icon ripple btn-sm"
          >
            <i class="i-Close-Window"></i>
            {{$t('Del')}}
          </button>
        </b-col>
      </b-row>
      <div class="invoice" id="print_Invoice">
        <div class="invoice-print">
          <b-row class="justify-content-md-center">
            <h4 class="font-weight-bold">{{$t('ReturnDetail')}} : {{sale_return.Ref}}</h4>
          </b-row>
          <hr>
          <b-row class="mt-5">
            <b-col lg="4" md="4" sm="12" class="mb-4">
              <h5 class="font-weight-bold">{{$t('Customer_Info')}}</h5>

              <div>{{sale_return.client_name}}</div>
              <div>{{sale_return.client_email}}</div>
              <div>{{sale_return.client_phone}}</div>
              <div>{{sale_return.client_adr}}</div>
            </b-col>
            <b-col lg="4" md="4" sm="12" class="mb-4">
              <h5 class="font-weight-bold">{{$t('Company_Info')}}</h5>
              <div>{{company.CompanyName}}</div>
              <div>{{company.email}}</div>
              <div>{{company.CompanyPhone}}</div>
              <div>{{company.CompanyAdress}}</div>
            </b-col>
            <b-col lg="4" md="4" sm="12" class="mb-4">
              <h5 class="font-weight-bold">{{$t('Return_Info')}}</h5>

              <div>{{$t('Reference')}} : {{sale_return.Ref}}</div>
              <div>{{$t('Sale_Ref')}} : {{sale_return.sale_ref}}</div>
              <div>
                {{$t('PaymentStatus')}} :
                <span
                  v-if="sale_return.payment_status == 'paid'"
                  class="badge badge-outline-success"
                >{{$t('Paid')}}</span>
                <span
                  v-else-if="sale_return.payment_status == 'partial'"
                  class="badge badge-outline-primary"
                >{{$t('partial')}}</span>
                <span v-else class="badge badge-outline-warning">{{$t('Unpaid')}}</span>
              </div>
              <div>{{$t('warehouse')}} : {{sale_return.warehouse}}</div>
              <div>
                {{$t('Status')}} :
                <span
                  v-if="sale_return.statut == 'received'"
                  class="badge badge-outline-success"
                >{{$t('Received')}}</span>
                <span v-else class="badge badge-outline-info">{{$t('Pending')}}</span>
              </div>
            </b-col>
          </b-row>
          <b-row class="mt-3">
            <b-col md="12">
              <h5 class="font-weight-bold">{{$t('list_product_returns')}}</h5>
              <div class="alert alert-danger">{{$t('products_refunded_alert')}}</div>
              <div class="table-responsive">
                <table class="table table-hover table-md">
                  <thead class="bg-gray-300">
                    <tr>
                      <th scope="col">{{$t('ProductName')}}</th>
                      <th scope="col">{{$t('Net_Unit_Price')}}</th>
                      <th scope="col">{{$t('Qty_return')}}</th>
                      <th scope="col">{{$t('UnitPrice')}}</th>
                      <th scope="col">{{$t('Discount')}}</th>
                      <th scope="col">{{$t('Tax')}}</th>
                      <th scope="col">{{$t('SubTotal')}}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="detail in details">
                      <td><span>{{detail.code}} ({{detail.name}})</span>
                        <p v-show="detail.is_imei && detail.imei_number !==null ">{{$t('IMEI_SN')}} : {{detail.imei_number}}</p>
                      </td>
                      <td>{{currentUser.currency}} {{formatNumber(detail.Net_price,3)}}</td>
                      <td>{{formatNumber(detail.quantity,2)}} {{detail.unit_sale}}</td>
                      <td>{{currentUser.currency}} {{formatNumber(detail.price,2)}}</td>
                      <td>{{currentUser.currency}} {{formatNumber(detail.DiscountNet,2)}}</td>
                      <td>{{currentUser.currency}} {{formatNumber(detail.taxe,2)}}</td>
                      <td>{{currentUser.currency}} {{detail.total.toFixed(2)}}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </b-col>
            <div class="offset-md-9 col-md-3 mt-4">
              <table class="table table-striped table-sm">
                <tbody>
                  <tr>
                    <td>{{$t('OrderTax')}}</td>
                    <td>
                      <span>{{currentUser.currency}} {{sale_return.TaxNet.toFixed(2)}} ({{formatNumber(sale_return.tax_rate,2)}} %)</span>
                    </td>
                  </tr>
                  <tr>
                    <td>{{$t('Discount')}}</td>
                    <td>{{currentUser.currency}} {{sale_return.discount.toFixed(2)}}</td>
                  </tr>
                  <tr>
                    <td>{{$t('Shipping')}}</td>
                    <td>{{currentUser.currency}} {{sale_return.shipping.toFixed(2)}}</td>
                  </tr>
                  <tr>
                    <td>
                      <span class="font-weight-bold">{{$t('Total')}}</span>
                    </td>
                    <td>
                      <span
                        class="font-weight-bold"
                      >{{currentUser.currency}} {{sale_return.GrandTotal}}</span>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <span class="font-weight-bold">{{$t('Paid')}}</span>
                    </td>
                    <td>
                      <span
                        class="font-weight-bold"
                      >{{currentUser.currency}} {{sale_return.paid_amount}}</span>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <span class="font-weight-bold">{{$t('Due')}}</span>
                    </td>
                    <td>
                      <span
                        class="font-weight-bold"
                      >{{currentUser.currency}} {{sale_return.due}}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </b-row>
          <hr v-show="sale_return.note">
          <b-row class="mt-4">
           <b-col md="12">
             <p>{{sale_return.note}}</p>
           </b-col>
        </b-row>
        </div>
      </div>
    </b-card>
  </div>
</template>


<script>
import { mapActions, mapGetters } from "vuex";
import NProgress from "nprogress";

export default {
  computed: mapGetters(["currentUserPermissions", "currentUser"]),
  metaInfo: {
    title: "Detail Sale Return"
  },

  data() {
    return {
      isLoading: true,
      sale_return: {},
      details: [],
      company: {},
      email: {}
    };
  },

  methods: {
    //-----------------------------------  Sale Return PDF -------------------------\\
    Return_PDF() {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      let id = this.$route.params.id;
     
       axios
        .get(`return_sale_pdf/${id}`, {
          responseType: "blob", // important
          headers: {
            "Content-Type": "application/json"
          }
        })
        .then(response => {
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement("a");
          link.href = url;
          link.setAttribute(
            "download",
            "Sale_Return-" + this.sale_return.Ref + ".pdf"
          );
          document.body.appendChild(link);
          link.click();
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
        })
        .catch(() => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
        });
    },

    //------------------------------ Print -------------------------\\
    print() {
      this.$htmlToPaper('print_Invoice');
    },

     //------ Toast
    makeToast(variant, msg, title) {
      this.$root.$bvToast.toast(msg, {
        title: title,
        variant: variant,
        solid: true
      });
    },


    //------------------------------Formetted Numbers -------------------------\\
    formatNumber(number, dec) {
      const value = (typeof number === "string"
        ? number
        : number.toString()
      ).split(".");
      if (dec <= 0) return value[0];
      let formated = value[1] || "";
      if (formated.length > dec)
        return `${value[0]}.${formated.substr(0, dec)}`;
      while (formated.length < dec) formated += "0";
      return `${value[0]}.${formated}`;
    },

    //----------------------------------- Get Details Sale Return ------------------------------\\
    Get_Details() {
      let id = this.$route.params.id;
      axios
        .get(`returns/sale/${id}`)
        .then(response => {
          this.sale_return = response.data.sale_Return;
          this.details = response.data.details;
          this.company = response.data.company;
          this.isLoading = false;
        })
        .catch(response => {
          setTimeout(() => {
            this.isLoading = false;
          }, 500);
        });
    },

    //---------------------  Delete Return ------------------------\\
    Delete_Return() {
      let id = this.$route.params.id;
      this.$swal({
        title: this.$t("Delete_Title"),
        text: this.$t("Delete_Text"),
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: this.$t("Delete_cancelButtonText"),
        confirmButtonText: this.$t("Delete_confirmButtonText")
      }).then(result => {
        if (result.value) {
          axios
            .delete("returns/sale/" + id)
            .then(() => {
              this.$swal(
                this.$t("Delete_Deleted"),
                this.$t("Deleted_in_successfully"),
                "success"
              );
              this.$router.push({ name: "index_sale_return" });
            })
            .catch(() => {
              this.$swal(
                this.$t("Delete_Failed"),
                this.$t("Delete_Therewassomethingwronge"),
                "warning"
              );
            });
        }
      });
    }
  }, //end Methods

  //----------------------------- Created function-------------------

  created: function() {
    this.Get_Details();
  }
};
</script>