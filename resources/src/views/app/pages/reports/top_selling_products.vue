<template>
  <div class="main-content">
    <breadcumb :page="$t('Top_Selling_Products')" :folder="$t('Reports')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <b-col md="12" class="text-center" v-if="!isLoading">
        <date-range-picker 
          v-model="dateRange" 
          :startDate="startDate" 
          :endDate="endDate" 
           @update="Submit_filter_dateRange"
          :locale-data="locale" > 

          <template v-slot:input="picker" style="min-width: 350px;">
              {{ picker.startDate.toJSON().slice(0, 10)}} - {{ picker.endDate.toJSON().slice(0, 10)}}
          </template>        
        </date-range-picker>
      </b-col>

      <vue-good-table
        v-if="!isLoading"
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="products"
        @on-page-change="onPageChange"
        @on-per-page-change="onPerPageChange"
        @on-search="onSearch_products"
          :search-options="{
            placeholder: $t('Search_this_table'),
            enabled: true,
        }"
        :pagination-options="{
        enabled: true,
        mode: 'records',
        nextLabel: 'next',
        prevLabel: 'prev',
      }"
        styleClass="mt-5 table-hover tableOne vgt-table"
      >
      <div slot="table-actions" class="mt-2 mb-3">
        <b-button @click="export_PDF()" size="sm" variant="outline-success ripple m-1">
          <i class="i-File-Copy"></i> PDF
        </b-button>

         <vue-excel-xlsx
              class="btn btn-sm btn-outline-danger ripple m-1"
              :data="products"
              :columns="columns"
              :file-name="'product_report'"
              :file-type="'xlsx'"
              :sheet-name="'product_report'"
              >
              <i class="i-File-Excel"></i> EXCEL
          </vue-excel-xlsx>

      </div>
        <template slot="table-row" slot-scope="props">
          <div v-if="props.column.field == 'total'">
            <span>{{currentUser.currency}} {{props.row.total}}</span>
          </div>
        </template>
      </vue-good-table>
      <!-- </b-card> -->
    </div>
</template>

<script>
import NProgress from "nprogress";
import { mapGetters } from "vuex";
import DateRangePicker from 'vue2-daterange-picker'
//you need to import the CSS manually
import 'vue2-daterange-picker/dist/vue2-daterange-picker.css'
import moment from 'moment'
import jsPDF from "jspdf";
import "jspdf-autotable";

export default {
  metaInfo: {
    title: "Top Selling Products"
  },
  components: { DateRangePicker },
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
      search_products:"",
      today_mode: true,
      startDate: "", 
      endDate: "", 
      dateRange: { 
       startDate: "", 
       endDate: "" 
      }, 
      locale:{ 
          //separator between the two ranges apply
          Label: "Apply", 
          cancelLabel: "Cancel", 
          weekLabel: "W", 
          customRangeLabel: "Custom Range", 
          daysOfWeek: moment.weekdaysMin(), 
          //array of days - see moment documenations for details 
          monthNames: moment.monthsShort(), //array of month names - see moment documenations for details 
          firstDay: 1 //ISO first day of week - see moment documenations for details
        },
    };
  },

  computed: {
    ...mapGetters(["currentUser"]),
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
          label: this.$t("TotalSales"),
          field: "total_sales",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        },

        {
          label: this.$t("TotalAmount"),
          field: "total",
          tdClass: "text-left",
          thClass: "text-left",
          sortable: false
        }
      ];
    }
  },

  methods: {

     
    onSearch_products(value) {
      this.search_products = value.searchTerm;
      this.Get_top_products(1);
    },

    //----------------------------------- Export PDF ------------------------------\\
    export_PDF() {
      var self = this;
      let pdf = new jsPDF("p", "pt");

      const fontPath = "/fonts/Vazirmatn-Bold.ttf";
      pdf.addFont(fontPath, "VazirmatnBold", "bold"); 
      pdf.setFont("VazirmatnBold"); 
      
      let columns = [
        { title: self.$t("ProductCode"), dataKey: "code" },
        { title: self.$t("ProductName"), dataKey: "name" },
        { title: self.$t("TotalSales"), dataKey: "total_sales" },
        { title: self.$t("TotalAmount"), dataKey: "total" },
      ];

      pdf.autoTable({
             columns: columns,
             body: self.products,
             startY: 70,
             theme: "grid", 
             didDrawPage: (data) => {
               pdf.setFont("VazirmatnBold");
               pdf.setFontSize(18);
               pdf.text("Top Selling Products", 40, 25);   
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

      });

      pdf.save("Top_Selling_Products.pdf");
    },

    //---- update Params Table
    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps);
    },

    //---- Event Page Change
    onPageChange({ currentPage }) {
      if (this.serverParams.page !== currentPage) {
        this.updateParams({ page: currentPage });
        this.Get_top_products(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_top_products(1);
      }
    },

     //----------------------------- Submit Date Picker -------------------\\
     Submit_filter_dateRange() {
      var self = this;
      self.startDate =  self.dateRange.startDate.toJSON().slice(0, 10);
      self.endDate = self.dateRange.endDate.toJSON().slice(0, 10);
      self.Get_top_products(1);
    },


    get_data_loaded() {
      var self = this;
      if (self.today_mode) {
        let startDate = new Date("01/01/2000");  // Set start date to "01/01/2000"
        let endDate = new Date();  // Set end date to current date

        self.startDate = startDate.toISOString();
        self.endDate = endDate.toISOString();

        self.dateRange.startDate = startDate.toISOString();
        self.dateRange.endDate = endDate.toISOString();
      }
    },

    //----------------------------- Get_top_products------------------\\
    Get_top_products(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      this.get_data_loaded();

      axios
        .get(
          "report/top_products?page=" +
            page +
            "&limit=" +
            this.limit +
            "&to=" +
            this.endDate +
            "&from=" +
            this.startDate +
            "&search=" +
            this.search_products
        )
        .then(response => {
          this.products = response.data.products;
          this.totalRows = response.data.totalRows;
          // Complete the animation of theprogress bar.
          NProgress.done();
          this.isLoading = false;
          this.today_mode = false;
        })
        .catch(response => {
          // Complete the animation of theprogress bar.
          NProgress.done();
          setTimeout(() => {
            this.isLoading = false;
            this.today_mode = false;
          }, 500);
        });
    }
  }, //end Methods

  //----------------------------- Created function------------------- \\

  created: function() {
    this.Get_top_products(1);
  }
};
</script>