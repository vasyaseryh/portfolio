using ozon.Models;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;
using System.Windows.Documents;
using System.Windows.Input;
using System.Windows.Media;
using System.Windows.Media.Imaging;
using System.Windows.Navigation;
using System.IO;

namespace ozon.Views
{
    /// <summary>
    /// Логика взаимодействия для AddOrderWindow.xaml
    /// </summary>
    public partial class AddOrderWindow : Window
    {
        private List<Product> products;


        public AddOrderWindow()
        {

            InitializeComponent();
            LoadProducts();
        }

        private void LoadProducts()
        {
            using (var context = new OzonContext())
            {
                products = context.Products
                    .Where(p => p.Quantity > 0)
                    .ToList()
                    .Select(p => new Product
                    {
                        Id = p.Id,
                        Name = p.Name,
                        Description = p.Description,
                        ImgUrl = Path.GetFullPath(p.ImgUrl),
                        Quantity = p.Quantity,
                        Price = p.Price
                    })
                    .ToList();

                productsList.ItemsSource = products;
            }
        }

        private void SearchTextBox_TextChanged(object sender, TextChangedEventArgs e)
        {
            using (var context = new OzonContext())
            {
                var searchTerm = SearchTextBox.Text.Trim().ToLower();

                products = context.Products
                    .Where(p => string.IsNullOrEmpty(searchTerm) || p.Name.ToLower().Contains(searchTerm))
                    .ToList()
                    .Select(p => new Product
                    {
                        Id = p.Id,
                        Name = p.Name,
                        Description = p.Description,
                        ImgUrl = Path.GetFullPath(p.ImgUrl),
                        Quantity = p.Quantity,
                        Price = p.Price
                    })
                    .ToList();

                productsList.ItemsSource = products;
            }
        }

        // Тогда метод OnProductSelectionChanged будет работать как было изначально:
        private void OnProductSelectionChanged(object sender, SelectionChangedEventArgs e)
        {
            var selectedProduct = productsList.SelectedItem as Product;
            if (selectedProduct != null)
            {
                new BuyWindow(selectedProduct).ShowDialog();
            }
        }

        private void ShowOrders_Click(object sender, RoutedEventArgs e)
        {
            WatchOrdersWindow ordersWindow = new WatchOrdersWindow(); // CurrentUserId должен быть определен заранее
            ordersWindow.ShowDialog();
        }






    }
}
