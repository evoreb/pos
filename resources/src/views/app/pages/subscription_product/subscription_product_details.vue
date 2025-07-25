<template>
  <div class="main-content">
    <breadcumb :page="$t('Subscription_details')" :folder="$t('Subscriptions')"/>
    <div v-if="loading" class="loading_page spinner spinner-primary mr-3"></div>

    <!-- Subscription Details Card -->
    <b-card class="shadow-sm border-0 p-3" v-if="!loading">
      <b-row>
        <!-- Left Side: Client Info -->
        <b-col md="4" class="text-center">
          <b-avatar variant="primary" size="5rem">
            <i class="i-User"></i>
          </b-avatar>
          <h5 class="mt-3">{{ subscription.client }}</h5>
          <b-badge :variant="getStatusVariant(subscription.status)" class="mt-2">
            {{ subscription.status }}
          </b-badge>
        </b-col>

        <!-- Right Side: Subscription Info -->
        <b-col md="8">
          <b-list-group flush>
            <b-list-group-item class="d-flex align-items-center">
              <i class="i-Box text-primary mr-2"></i>
              <strong style=" padding-right: 10px; ">{{$t('ProductName')}}: </strong> {{ subscription.product }}
            </b-list-group-item>

            <b-list-group-item class="d-flex align-items-center">
              <i class="i-Warehouse text-success mr-2"></i>
              <strong style=" padding-right: 10px; ">{{$t('warehouse')}}: </strong> {{ subscription.warehouse }}
            </b-list-group-item>

            <b-list-group-item class="d-flex align-items-center">
              <i class="i-Repeat text-warning mr-2"></i>
              <strong style=" padding-right: 10px; ">{{$t('Billing_Cycle')}}: </strong> {{ subscription.billing_cycle }}
            </b-list-group-item>

            <b-list-group-item class="d-flex align-items-center">
              <i class="i-Money text-danger mr-2"></i>
              <strong style=" padding-right: 10px; ">{{$t('Price_Per_Cycle')}}: </strong>
              <span class="text-success"> {{ subscription.price_per_cycle }}</span>
            </b-list-group-item>

            <b-list-group-item class="d-flex align-items-center">
              <i class="i-Calendar text-info mr-2"></i>
              <strong style=" padding-right: 10px; ">{{$t('next_billing_date')}}: </strong>
              <b-badge variant="info"> {{ subscription.next_billing_date }}</b-badge>
            </b-list-group-item>
          </b-list-group>

        
        </b-col>
      </b-row>



    </b-card>

    
    <!-- Invoices Table -->
    <b-card title="Invoices Report" class="mt-4 shadow-sm border-0" v-if="!loading">
      <b-table :items="invoices" :fields="invoiceFields" bordered responsive>
        <template #cell(ref)="data">
          <i class="i-File-Text text-primary"></i>
          <b-link :href="`/app/sales/detail/${data.item.sale_id}`">
            {{ data.item.ref }}
          </b-link>
        </template>

        <template #cell(date)="data">
          <i class="i-Calendar text-warning"></i> {{ data.item.date }}
        </template>

        <template #cell(total)="data">
          <i class="i-Money text-success"></i> ${{ data.item.total }}
        </template>

        <template #cell(status)="data">
          <b-badge :variant="getStatusVariant(data.item.status)">
            {{ data.item.status }}
          </b-badge>
        </template>
      </b-table>
    </b-card>
  </div>
</template>

<script>
export default {
  data() {
    return {
      loading: false,
      subscription: {},
      invoices: [],
      invoiceFields: []
    };
  },

  mounted() {
    const subscriptionId = this.$route.params.id; // or pass manually
    this.loadSubscription(subscriptionId);
  },
  methods: {

    async loadSubscription(id) {
    this.loading = true;

    try {
      const { data } = await axios.get(`/subscriptions/${id}`);
      this.subscription = data.subscription;
      this.invoices = data.invoices;
      this.invoiceFields = data.invoiceFields;
    } catch (error) {
      this.$bvToast.toast("Unable to load subscription details", {
        variant: "danger",
        title: "Error",
        solid: true,
      });
    } finally {
      this.loading = false;
    }
  },

    initiateInitialPayment(subscriptionId) {
    this.$bvModal.msgBoxConfirm("Are you sure you want to initiate the first payment?", {
      title: "Confirm Payment",
      okTitle: "Yes",
      cancelTitle: "Cancel",
      centered: true
    }).then(confirmed => {
      if (confirmed) {
        window.location.href = `/api/subscriptions/${subscriptionId}/pay`; // or call an API
      }
    });
  },

    getStatusVariant(status) {
      const variants = {
        active: "success",
        unpaid: "danger",
        partial: "warning",
        paid: "success",
      };
      return variants[status] || "secondary";
    },
  },
};
</script>

<style>
.i-User,
.i-Box,
.i-Warehouse,
.i-Repeat,
.i-Money,
.i-Calendar,
.i-File-Text {
  font-size: 1.2rem;
}
</style>
