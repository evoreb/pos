<template>
  <div class="main-content">
    <breadcumb :page="$t('deposit_Category')" :folder="$t('Expenses')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <div v-else>
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="categories"
        @on-page-change="onPageChange"
        @on-per-page-change="onPerPageChange"
        @on-sort-change="onSortChange"
        @on-search="onSearch"
        :search-options="{
        enabled: true,
        placeholder: $t('Search_this_table'),  
      }"
       
        :pagination-options="{
        enabled: true,
        mode: 'records',
        nextLabel: 'next',
        prevLabel: 'prev',
      }"
        styleClass="tableOne table-hover vgt-table"
      >
        <div slot="selected-row-actions">
          <button class="btn btn-danger btn-sm" @click="delete_by_selected()">{{$t('Del')}}</button>
        </div>
        <div slot="table-actions" class="mt-2 mb-3">
          <b-button
            v-if="currentUserPermissions && currentUserPermissions.includes('deposit_add')"
            @click="New_Category()"
            size="sm"
            variant="primary ripple m-1"
          >
            <i class="i-Add"></i>
            {{$t('Add')}}
          </b-button>
        </div>

        <template slot="table-row" slot-scope="props">
          <span v-if="props.column.field == 'actions'">
            <a
              @click="Edit_Category(props.row)"
              v-if="currentUserPermissions && currentUserPermissions.includes('deposit_edit')"
              title="Edit"
              class="cursor-pointer"
              v-b-tooltip.hover
            >
              <i class="i-Edit text-25 text-success"></i>
            </a>
            <a
              title="Delete"
              class="cursor-pointer"
              v-b-tooltip.hover
              v-if="currentUserPermissions && currentUserPermissions.includes('deposit_delete')"
              @click="Delete_Category(props.row.id)"
            >
              <i class="i-Close-Window text-25 text-danger"></i>
            </a>
          </span>
        </template>
      </vue-good-table>
    </div>

    <validation-observer ref="Create_Category">
      <b-modal hide-footer size="md" id="New_Category" :title="editmode?$t('Edit'):$t('Add')">
        <b-form @submit.prevent="Submit_Category">
          <b-row>
            <!-- Name Category -->
            <b-col md="12">
              <validation-provider
                name="Name category"
                :rules="{ required: true}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('title') + ' ' + '*'">
                  <b-form-input
                    :state="getValidationState(validationContext)"
                    aria-describedby="category-feedback"
                    label="name"
                    v-model="category.title"
                  ></b-form-input>
                  <b-form-invalid-feedback id="category-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <b-col md="12" class="mt-3">
              <b-button variant="primary" type="submit"  :disabled="SubmitProcessing"><i class="i-Yes me-2 font-weight-bold"></i> {{$t('submit')}}</b-button>
                <div v-once class="typo__p" v-if="SubmitProcessing">
                  <div class="spinner sm spinner-primary mt-3"></div>
                </div>
            </b-col>
          </b-row>
        </b-form>
      </b-modal>
    </validation-observer>
  </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex";
import NProgress from "nprogress";

export default {
  metaInfo: {
    title: "Deposit Category"
  },
  data() {
    return {
      isLoading: true,
      SubmitProcessing:false,
      serverParams: {
        columnFilters: {},
        sort: {
          field: "id",
          type: "desc"
        },
        page: 1,
        perPage: 10
      },
      selectedIds: [],
      totalRows: "",
      search: "",
      categories: [],
      editmode: false,
      limit: "10",
      category: {
        id: "",
        title: "",
      }
    };
  },

  computed: {
    ...mapGetters(["currentUserPermissions"]),
    columns() {
      return [
        {
          label: this.$t("title"),
          field: "title",
          tdClass: "text-left",
          thClass: "text-left"
        },

        {
          label: this.$t("Action"),
          field: "actions",
          html: true,
          tdClass: "text-right",
          thClass: "text-right",
          sortable: false
        }
      ];
    }
  },

  methods: {
    //------------- Submit Validation Create & Edit Category
    Submit_Category() {
      this.$refs.Create_Category.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          if (!this.editmode) {
            this.Create_Category();
          } else {
            this.Update_Category();
          }
        }
      });
    },

    //------ Toast
    makeToast(variant, msg, title) {
      this.$root.$bvToast.toast(msg, {
        title: title,
        variant: variant,
        solid: true
      });
    },

    //------ Update Params Table
    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps);
    },

    //---- Event Page Change
    onPageChange({ currentPage }) {
      if (this.serverParams.page !== currentPage) {
        this.updateParams({ page: currentPage });
        this.Get_Categories(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_Categories(1);
      }
    },

    //---- Event Select Rows
    selectionChanged({ selectedRows }) {
      this.selectedIds = [];
      selectedRows.forEach((row, index) => {
        this.selectedIds.push(row.id);
      });
    },

    //------ Event Sort change
    onSortChange(params) {
      this.updateParams({
        sort: {
          type: params[0].type,
          field: params[0].field
        }
      });
      this.Get_Categories(this.serverParams.page);
    },

    //------ Event Search
    onSearch(value) {
      this.search = value.searchTerm;
      this.Get_Categories(this.serverParams.page);
    },

    //------ Event Validation State
    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    //--------------------------Show Modal (new Category) ----------------\\
    New_Category() {
      this.reset_Form();
      this.editmode = false;
      this.$bvModal.show("New_Category");
    },

    //-------------------------- Show Modal (Edit Category) ----------------\\
    Edit_Category(cat) {
      this.Get_Categories(this.serverParams.page);
      this.reset_Form();
      this.category = cat;
      this.editmode = true;
      this.$bvModal.show("New_Category");
    },

    //--------------------------- reset Form ----------------\\
    reset_Form() {
      this.category = {
        id: "",
        title: "",
      };
    },

    //--------------------------Get ALL Categories ---------------------------\\
    Get_Categories(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get(
          "deposits_category?page=" +
            page +
            "&SortField=" +
            this.serverParams.sort.field +
            "&SortType=" +
            this.serverParams.sort.type +
            "&search=" +
            this.search +
            "&limit=" +
            this.limit
        )
        .then(response => {
          this.categories = response.data.deposits_category;
          this.totalRows = response.data.totalRows;
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
    },

    //----------------------------------Create new Category ----------------\\
    Create_Category() {
      this.SubmitProcessing = true;
      axios
        .post("deposits_category", {
          title: this.category.title
        })
        .then(response => {
          Fire.$emit("event_Category_deposit");

          this.makeToast(
            "success",
            this.$t("Successfully_Created"),
            this.$t("Success")
          );
          this.SubmitProcessing = false;
        })
        .catch(error => {
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
          this.SubmitProcessing = false;
        });
    },

    //---------------------------------- Update Category ----------------\\
    Update_Category() {
      this.SubmitProcessing = true;
      axios
        .put("deposits_category/" + this.category.id, {
          title: this.category.title,
        })
        .then(response => {
          Fire.$emit("event_Category_deposit");

          this.makeToast(
            "success",
            this.$t("Successfully_Updated"),
            this.$t("Success")
          );
          this.SubmitProcessing = false;
        })
        .catch(error => {
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
          this.SubmitProcessing = false;
        });
    },

    //--------------------------- Delete Category----------------\\
    Delete_Category(id) {
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
            .delete("deposits_category/" + id)
            .then(() => {
              this.$swal(
                this.$t("Delete_Deleted"),
                this.$t("Deleted_in_successfully"),
                "success"
              );

              Fire.$emit("event_delete_category_deposit");
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
    },

   
  }, //end Methods

  //----------------------------- Created function-------------------

  created: function() {
    this.Get_Categories(1);

    Fire.$on("event_Category_deposit", () => {
      this.Get_Categories(this.serverParams.page);
      this.$bvModal.hide("New_Category");
    });

    Fire.$on("event_delete_category_deposit", () => {
      this.Get_Categories(this.serverParams.page);
    });
  }
};
</script>
