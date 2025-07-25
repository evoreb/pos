<template>
  <div class="main-content">
    <breadcumb :page="$t('ProductQuantityAlerts')" :folder="$t('Reports')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <vue-good-table
      v-if="!isLoading"
      mode="remote"
      :columns="columns"
      :totalRows="totalRows"
      :rows="products"
      @on-page-change="onPageChange"
      @on-per-page-change="onPerPageChange"
      :pagination-options="{
        enabled: true,
        mode: 'records',
        nextLabel: 'next',
        prevLabel: 'prev',
      }"
      styleClass="table-hover tableOne vgt-table"
    >
      <div slot="table-actions" class="mt-2 mb-3 quantity_alert_warehouse">
        <!-- warehouse -->
        <b-form-group :label="$t('warehouse')">
          <v-select
            @input="Selected_Warehouse"
            v-model="warehouse_id"
            :reduce="label => label.value"
            :placeholder="$t('Choose_Warehouse')"
            :options="warehouses.map(warehouses => ({label: warehouses.name, value: warehouses.id}))"
          />
        </b-form-group>
      </div>

      <div slot="table-actions" class="mt-2 mb-3">
        
          <b-button @click="stock_alert_PDF()" size="sm" variant="outline-success ripple m-1">
            <i class="i-File-Copy"></i> PDF
          </b-button>
           <vue-excel-xlsx
              class="btn btn-sm btn-outline-danger ripple m-1"
              :data="products"
              :columns="columns"
              :file-name="'Alerts_report'"
              :file-type="'xlsx'"
              :sheet-name="'Alerts_report'"
              >
              <i class="i-File-Excel"></i> EXCEL
          </vue-excel-xlsx>
        </div>

      <template slot="table-row" slot-scope="props">
        <div v-if="props.column.field == 'stock_alert'">
          <span class="badge badge-outline-danger">{{props.row.stock_alert}}</span>
        </div>
      </template>
    </vue-good-table>
    <!-- </b-card> -->
  </div>
</template>

<script>
import NProgress from "nprogress";
import jsPDF from "jspdf";
import "jspdf-autotable";

export default {
  metaInfo: {
    title: "Products Alert"
  },
  data() {
    return {
      isLoading: true,
      serverParams: {
        sort: {
          field: "id",
          type: "desc"
        },
        page: 1,
        perPage: 10
      },
      limit: "10",
      totalRows: "",
      products: [],
      warehouses: [],
      warehouse_id: ""
    };
  },

  computed: {
    columns() {
      return [
        {
          label: this.$t("ProductCode"),
          field: "code",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("ProductName"),
          field: "name",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("warehouse"),
          field: "warehouse",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("Quantity"),
          field: "quantity",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },
        {
          label: this.$t("AlertQuantity"),
          field: "stock_alert",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        }
      ];
    }
  },

  methods: {

      //----------------------------------- Sales PDF ------------------------------\\
    stock_alert_PDF() {
      var self = this;
      let pdf = new jsPDF("p", "pt");

      const fontPath = "/fonts/Vazirmatn-Bold.ttf";
      pdf.addFont(fontPath, "VazirmatnBold", "bold"); 
      pdf.setFont("VazirmatnBold"); 

      let columns = [
        { title: self.$t("ProductCode"), dataKey: "code" },
        { title: self.$t("ProductName"), dataKey: "name" },
        { title: self.$t("warehouse"), dataKey: "warehouse" },
        { title: self.$t("Quantity"), dataKey: "quantity" },
        { title: self.$t("AlertQuantity"), dataKey: "stock_alert" },
      ];

      
        // Calculate totals
        let totalquantity = self.products.reduce((sum, product) => sum + parseFloat(product.quantity || 0), 0);
        let totalstock_alert = self.products.reduce((sum, product) => sum + parseFloat(product.stock_alert || 0), 0);

        let footer = [{
          code: self.$t("Total"),
          name: '',
          warehouse: '',
          quantity: `${totalquantity.toFixed(2)}`,
          stock_alert: `${totalstock_alert.toFixed(2)}`,
          
        }];


      pdf.autoTable({
             columns: columns,
             body: self.products,
             foot: footer,
             startY: 70,
             theme: "grid", 
             didDrawPage: (data) => {
               pdf.setFont("VazirmatnBold");
               pdf.setFontSize(18);
               pdf.text("Stock Alert report", 40, 25);   
             },
             styles: {
               font: "VazirmatnBold", 
               halign: "center", // 
             },
             headStyles: {
               fillColor: [200, 200, 200], 
               textColor: [0, 0, 0], 
               fontStyle: "bold", 
             },
             footStyles: {
               fillColor: [230, 230, 230], 
               textColor: [0, 0, 0], 
               fontStyle: "bold", 
             },
      });

      pdf.save("Stock_alert_report.pdf");

    },


    //---- update Params Table
    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps);
    },

    //---- Event Page Change
    onPageChange({ currentPage }) {
      if (this.serverParams.page !== currentPage) {
        this.updateParams({ page: currentPage });
        this.Get_Stock_Alerts(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_Stock_Alerts(1);
      }
    },

    //---------------------- Event Select Warehouse ------------------------------\\
    Selected_Warehouse(value) {
      if (value === null) {
        this.warehouse_id = "";
      }
      this.Get_Stock_Alerts(1);
    },

    //----------------------------- Get Stock Alerts-------------------\\
    Get_Stock_Alerts(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get(
          "get_products_stock_alerts?page=" +
            page +
            "&warehouse=" +
            this.warehouse_id +
            "&limit=" +
            this.limit
        )
        .then(response => {
          this.products = response.data.products.data;
          this.warehouses = response.data.warehouses;
          this.totalRows = response.data.products.total;
          // Complete the animation of theprogress bar.
          NProgress.done();
          this.isLoading = false;
        })
        .catch(response => {
          // Complete the animation of theprogress bar.
          NProgress.done();
          setTimeout(() => {
            this.isLoading = false;
          }, 500);
        });
    }
  }, //end Methods

  //----------------------------- Created function------------------- \\

  created: function() {
    this.Get_Stock_Alerts(1);
  }
};
</script>