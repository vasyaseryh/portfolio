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
using System.Windows.Shapes;

namespace BootsApp
{
    public partial class AdminTovarWindow : Window
    {
        bootsEntities2 _context;
        public AdminTovarWindow()
        {
            InitializeComponent();
            _context = bootsEntities2.GetContext();
            DgTovar.ItemsSource = _context.Tovars.ToList();
        }

        private void TextBox_TextChanged(object sender, TextChangedEventArgs e)
        {

        }

        private void GoHomeButt(object sender, RoutedEventArgs e)
        {
            new MainWindow().Show();
            this.Close();
        }

        private void AddButt(object sender, RoutedEventArgs e)
        {
            new AdminAddTovarWindow().Show();
        }

        private void ChangeButt(object sender, RoutedEventArgs e)
        {
            var item = DgTovar.SelectedItem;
            new AdminAddTovarWindow(item as Tovar).Show();
 
        }

        private void RefreshButt(object sender, RoutedEventArgs e)
        {
            _context = bootsEntities2.GetContext();
            DgTovar.ItemsSource = _context.Tovars.ToList();
        }

        private void DelButt(object sender, RoutedEventArgs e)
        {
            _context.Tovars.Remove(DgTovar.SelectedItem as Tovar);
            _context.SaveChanges();
        }

        private void AdminOrderButt(object sender, RoutedEventArgs e)
        {
            new AdminOrderWindow().Show();
            this.Close();
        }

        private void AdminMainButt(object sender, RoutedEventArgs e)
        {
            new AdminMainWindow().Show();
            this.Close();
        }
    }
}
